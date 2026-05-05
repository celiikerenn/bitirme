"""
Harcama CRUD ve aylık özet API endpoint'leri.
Laravel, oturumdaki user_id ile istek atar.
"""
from datetime import date
from decimal import Decimal
import io
import os
import re

import pytesseract
from PIL import Image
from fastapi import APIRouter, Depends, HTTPException, Query, UploadFile, File, Form
from sqlalchemy.orm import Session, joinedload
from sqlalchemy import func, extract

from app.database import get_db
from app.config import settings
from app.models_db import Expense, ExpenseCategory, User
from app.schemas import (
    ExpenseCreate,
    ExpenseUpdate,
    ExpenseResponse,
    ExpenseListResponse,
    MonthlyTotalResponse,
)

router = APIRouter(prefix="/expenses", tags=["expenses"])


def _configure_tesseract_path() -> None:
    """
    Windows ortamında PATH'e eklenmemiş olsa bile yaygın kurulum yolunu kullan.
    Opsiyonel olarak TESSERACT_CMD env değişkeni ile ezilebilir.
    """
    configured_cmd = settings.TESSERACT_CMD or os.getenv("TESSERACT_CMD")
    if configured_cmd and os.path.exists(configured_cmd):
        pytesseract.pytesseract.tesseract_cmd = configured_cmd
        return

    common_windows_path = r"C:\Program Files\Tesseract-OCR\tesseract.exe"
    if os.path.exists(common_windows_path):
        pytesseract.pytesseract.tesseract_cmd = common_windows_path


_configure_tesseract_path()


def _receipt_image_to_ocr_text(image: Image.Image) -> str:
    """
    Fiş görüntüsünden düz metin üretir.

    Tesseract OCR kullanılır (yerel binary); harici yapay zeka / vision API çağrısı yoktur.
    """
    try:
        return pytesseract.image_to_string(image, lang="tur+eng", config="--psm 6")
    except pytesseract.TesseractNotFoundError as e:
        raise HTTPException(
            status_code=503,
            detail=(
                "Tesseract OCR bulunamadı. Windows için kurun veya TESSERACT_CMD ile "
                "tesseract.exe yolunu ayarlayın: https://github.com/UB-Mannheim/tesseract/wiki"
            ),
        ) from e
    except Exception:
        try:
            return pytesseract.image_to_string(image, lang="eng", config="--psm 6")
        except Exception as e2:
            raise HTTPException(
                status_code=400,
                detail=f"OCR başarısız (Tesseract). tur/eng dil paketleri kurulu mu? Hata: {e2}",
            ) from e2


def _parse_money_candidates(text: str) -> list[Decimal]:
    candidates: list[Decimal] = []
    for raw in re.findall(r"\d+[.,]\d{2}", text):
        normalized = raw.replace(".", "").replace(",", ".")
        try:
            value = Decimal(normalized)
            if value > 0:
                candidates.append(value)
        except Exception:
            continue
    return candidates


# Fişte işlem tarihini ararken satır ipuçları (Türkçe / İngilizce)
_DATE_LINE_HINTS = (
    "TARIH", "TARİH", "TARIHI", "TARİHİ", "DATE", "FIS", "FIŞ", "FİŞ",
    "ISLEM", "İŞLEM", "SATIS", "SATIŞ", "Z NO", "ZNO", "BELGE", "TUTAR", "TOP",
    "ODEME", "ÖDEME", "FATURA", "DUZENLENME", "DÜZENLENME", "KASİYER", "KASA",
)

# Makul geçmiş (yanlış OCR yılı elenir)
_EARLIEST_RECEIPT_DATE = date(2015, 1, 1)


def _try_parse_date_triple(g1: str, g2: str, g3: str) -> date | None:
    """g1,g2,g3 ya YYYY-MM-DD ya da GG-AA-YYYY (TR) biçimi."""
    try:
        if len(g1) == 4:
            y, mth, d = int(g1), int(g2), int(g3)
        else:
            d, mth = int(g1), int(g2)
            y = int(g3)
            if y < 100:
                y += 2000
        return date(y, mth, d)
    except (ValueError, OverflowError):
        return None


def _collect_all_dates_from_text(text: str) -> list[date]:
    """OCR çıktısından olası tüm tarih adayları (Türk fişleri: boşluklu ayırıcı)."""
    found: list[date] = []
    patterns = [
        r"\b(\d{4})[.\s\-/]+(\d{1,2})[.\s\-/]+(\d{1,2})\b",  # 2026-05-05, 2026 / 05 / 05
        r"\b(\d{1,2})[.\s\-/]+(\d{1,2})[.\s\-/]+(\d{2,4})\b",  # 05.05.2026, 05 / 05 / 26
    ]
    for pattern in patterns:
        for m in re.finditer(pattern, text):
            cand = _try_parse_date_triple(*m.groups())
            if cand is not None:
                found.append(cand)
    return found


def _date_hint_score(d: date, lines: list[str]) -> int:
    """Tarih, fiş üzerinde 'tarih/işlem' satırında geçiyorsa yüksek skor."""
    day_s = str(d.day)
    month_s = str(d.month)
    year_s = str(d.year)
    yshort = str(d.year % 100)
    best = 0
    for ln in lines:
        lu = ln.upper().replace("İ", "I").replace("Ş", "S")
        if year_s not in ln and yshort not in ln:
            continue
        # Ay/gün yaklaşık eşleşmesi (OCR kayması için gevşek)
        if day_s not in ln and month_s not in ln:
            continue
        for hint in _DATE_LINE_HINTS:
            if hint in lu or hint.replace("İ", "I") in lu:
                best = max(best, 3)
                break
        best = max(best, 1)
    return best


def _select_receipt_date(valid_dates: list[date], lines: list[str]) -> date:
    """
    İşlem tarihi: genelde fişteki en güncel (<= bugün) tarih veya tarih anahtar kelimeli satırdaki tarih.
    """
    today_d = date.today()
    uniq = sorted(set(valid_dates))
    uniq = [d for d in uniq if _EARLIEST_RECEIPT_DATE <= d <= today_d]
    if not uniq:
        return today_d
    scored = [( _date_hint_score(d, lines), d) for d in uniq]
    scored.sort(key=lambda x: (-x[0], -x[1].toordinal()))
    return scored[0][1]


def _extract_receipt_fields(
    text: str,
    *,
    extra_date_text: str | None = None,
    extra_date_lines: list[str] | None = None,
) -> tuple[str, Decimal, date]:
    cleaned = text.replace("\r", "\n")
    lines = [ln.strip() for ln in cleaned.split("\n") if ln.strip()]
    if not lines:
        raise HTTPException(status_code=400, detail="No readable text found on receipt.")

    store_name = ""
    preferred_keywords = ("ISPARK", "OTOPARK", "MARKET", "A101", "BIM", "MIGROS", "CARREFOUR")
    for ln in lines[:12]:
        normalized = re.sub(r"[^A-Za-z0-9ÇĞİÖŞÜçğıöşü ]+", " ", ln).upper()
        normalized = re.sub(r"\s+", " ", normalized).strip()
        if any(k in normalized for k in preferred_keywords):
            store_name = normalized.replace("1SPARK", "ISPARK").replace("I SPARK", "ISPARK")[:120]
            break

    for ln in lines[:8]:
        if store_name:
            break
        if len(ln) < 2:
            continue
        digit_count = sum(ch.isdigit() for ch in ln)
        if digit_count > max(3, len(ln) // 2):
            continue
        store_name = re.sub(r"\s+", " ", ln).strip()
        store_name = store_name.replace("1SPARK", "ISPARK").replace("I SPARK", "ISPARK")[:120]
        break
    if not store_name:
        store_name = "Receipt expense"

    date_blob = cleaned
    date_lines = list(lines)
    if extra_date_text:
        date_blob = cleaned + "\n" + extra_date_text.replace("\r", "\n")
    if extra_date_lines:
        date_lines.extend(extra_date_lines)
    raw_dates = _collect_all_dates_from_text(date_blob)
    date_value = _select_receipt_date(raw_dates, date_lines)

    # Toplam satırlarını önce dene (TOP/TUTAR/TOTAL/KREDI/NAKIT)
    amount_value = None
    total_line_keywords = ("TOP", "TUTAR", "TOTAL", "KREDI", "KREDİ", "NAKIT", "NAKİT")
    for ln in lines:
        ln_upper = ln.upper()
        if any(k in ln_upper for k in total_line_keywords):
            line_values = _parse_money_candidates(ln)
            if line_values:
                amount_value = max(line_values)
                break

    number_candidates = _parse_money_candidates(cleaned)
    if not number_candidates:
        raise HTTPException(status_code=400, detail="Amount could not be detected from receipt.")
    if amount_value is None:
        amount_value = max(number_candidates)

    return store_name, amount_value, date_value


def _auto_pick_category(db: Session, ocr_text: str) -> ExpenseCategory | None:
    text_upper = ocr_text.upper()

    # Kategori adları İngilizce (Food, Transport, …); fiş üzerindeki kelimeler TR/EN karışık olabilir.
    keyword_map = [
        (["transport"], ["OTOPARK", "PARK", "TAXI", "METRO", "BUS", "DOLMUS", "ULAŞIM", "ULASIM", "ISPARK"]),
        (["grocer", "grocery"], ["MARKET", "MIGROS", "BIM", "A101", "CARREFOUR", "SOK"]),
        (["utilities", "utility"], ["FATURA", "ELEKTRIK", "ELEKTRİK", "DOGALGAZ", "SU", "INTERNET"]),
        (["health"], ["ECZANE", "HASTANE", "DOKTOR", "SAGLIK", "SAĞLIK"]),
        (["entertainment"], ["SINEMA", "KONSER", "EGLENCE", "EĞLENCE"]),
        (["clothing"], ["GIYIM", "GİYİM", "AYAKKABI", "MONT", "CEKET"]),
        (["rent"], ["KIRA", "KİRA"]),
        (["food"], ["RESTORAN", "RESTAURANT", "CAFE", "CAFÉ", "KEBAP", "YEMEK"]),
        (["education"], ["OKUL", "UNIVERSITY", "ÜNIVERSİTE", "KURS"]),
    ]

    all_categories = db.query(ExpenseCategory).all()

    def find_category_by_aliases(aliases: list[str]) -> ExpenseCategory | None:
        for cat in all_categories:
            cat_name = (cat.name or "").strip().lower()
            for alias in aliases:
                if alias in cat_name:
                    return cat
        return None

    for aliases, keywords in keyword_map:
        if any(k in text_upper for k in keywords):
            matched = find_category_by_aliases(aliases)
            if matched is not None:
                return matched

    fallback = (
        db.query(ExpenseCategory)
        .filter(func.lower(ExpenseCategory.name).in_(["other", "diğer", "diger"]))
        .first()
    )
    if fallback is None:
        fallback = db.query(ExpenseCategory).order_by(ExpenseCategory.id.asc()).first()
    if fallback is None:
        fallback = ExpenseCategory(name="Other")
        db.add(fallback)
        db.commit()
        db.refresh(fallback)
    return fallback


def expense_to_response(exp: Expense) -> ExpenseResponse:
    """ORM Expense -> API response."""
    return ExpenseResponse(
        id=exp.id,
        user_id=exp.user_id,
        category_id=exp.category_id,
        category_name=exp.category.name if exp.category else "",
        amount=exp.amount,
        description=exp.description,
        expense_date=exp.expense_date,
        created_at=exp.created_at,
    )


@router.post("", response_model=ExpenseResponse)
def create_expense(data: ExpenseCreate, db: Session = Depends(get_db)):
    """
    Yeni harcama ekler.
    Laravel, giriş yapmış kullanıcının id'sini user_id olarak gönderir.
    """
    try:
        # Kategori var mı kontrol
        if data.expense_date > date.today():
            raise HTTPException(status_code=400, detail="Expense date cannot be in the future.")

        category = db.query(ExpenseCategory).filter(ExpenseCategory.id == data.category_id).first()
        if not category:
            raise HTTPException(status_code=400, detail="Invalid category id.")

        user = db.query(User).filter(User.id == data.user_id).first()
        if not user:
            raise HTTPException(status_code=400, detail="Invalid user id. Please login/register again.")

        expense = Expense(
            user_id=data.user_id,
            category_id=data.category_id,
            amount=data.amount,
            description=data.description,
            expense_date=data.expense_date,
        )
        # Response için category ilişkisini hazır tut
        expense.category = category
        db.add(expense)
        db.commit()
        db.refresh(expense)
        return expense_to_response(expense)
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        err_msg = str(e)
        if hasattr(e, "orig"):
            err_msg = str(e.orig)
        elif hasattr(e, "__cause__") and e.__cause__:
            err_msg = str(e.__cause__)
        raise HTTPException(status_code=503, detail=f"Veritabanı hatası: {err_msg}") from e


@router.post("/ocr-create", response_model=ExpenseResponse)
async def create_expense_from_receipt(
    user_id: int = Form(...),
    receipt: UploadFile = File(...),
    db: Session = Depends(get_db),
):
    """
    Fiş görselinden Tesseract (pytesseract) ile OCR yapar; mağaza adı/tutar/tarih çıkarıp harcama oluşturur.
    Harici AI / bulut vision servisi kullanılmaz.
    """
    if not receipt.content_type or not receipt.content_type.startswith("image/"):
        raise HTTPException(status_code=400, detail="Only image files are supported.")

    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=400, detail="Invalid user id. Please login/register again.")

    try:
        raw = await receipt.read()
        image = Image.open(io.BytesIO(raw)).convert("L")
        # Çok büyük görsellerde OCR için boyut sınırla (Tesseract / bellek)
        max_side = 2000
        w, h = image.size
        if max(w, h) > max_side:
            ratio = max_side / float(max(w, h))
            image = image.resize(
                (max(1, int(w * ratio)), max(1, int(h * ratio))),
                Image.Resampling.LANCZOS,
            )
        # Basit threshold: fiş metnini keskinleştirir.
        image = image.point(lambda x: 0 if x < 170 else 255, mode="1")
        text = _receipt_image_to_ocr_text(image)
        cleaned_primary = text.replace("\r", "\n")
        extra_date_text: str | None = None
        extra_date_lines: list[str] | None = None
        if not _collect_all_dates_from_text(cleaned_primary):
            try:
                extra_date_text = pytesseract.image_to_string(image, lang="tur+eng", config="--psm 4")
            except Exception:
                extra_date_text = pytesseract.image_to_string(image, lang="eng", config="--psm 4")
            ex = extra_date_text.replace("\r", "\n")
            extra_date_lines = [ln.strip() for ln in ex.split("\n") if ln.strip()]
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(
            status_code=400,
            detail=f"Receipt image could not be processed: {str(e)}",
        ) from e

    store_name, amount_value, date_value = _extract_receipt_fields(
        text,
        extra_date_text=extra_date_text,
        extra_date_lines=extra_date_lines,
    )

    selected_category = _auto_pick_category(db, text)
    if selected_category is None:
        selected_category = db.query(ExpenseCategory).order_by(ExpenseCategory.id.asc()).first()
    if selected_category is None:
        raise HTTPException(status_code=400, detail="No expense category is available.")

    expense = Expense(
        user_id=user_id,
        category_id=selected_category.id,
        amount=amount_value,
        description=store_name,
        expense_date=date_value,
    )
    expense.category = selected_category

    try:
        db.add(expense)
        db.commit()
        db.refresh(expense)
    except Exception as e:
        db.rollback()
        err_msg = str(e.orig) if hasattr(e, "orig") else str(e)
        raise HTTPException(status_code=503, detail=f"Veritabanı hatası: {err_msg}") from e

    return expense_to_response(expense)


@router.get("", response_model=ExpenseListResponse)
def list_expenses_by_user(
    user_id: int = Query(..., description="Laravel'den gelen kullanıcı id"),
    skip: int = Query(0, ge=0),
    limit: int = Query(50, ge=1, le=200),
    db: Session = Depends(get_db),
):
    """
    Kullanıcıya ait harcamaları listeler (tarih sırasına göre, en yeni önce).
    """
    query = (
        db.query(Expense)
        .options(joinedload(Expense.category))
        .filter(Expense.user_id == user_id)
        .order_by(Expense.expense_date.desc(), Expense.created_at.desc())
    )
    total = query.count()
    expenses = query.offset(skip).limit(limit).all()
    return ExpenseListResponse(
        expenses=[expense_to_response(e) for e in expenses],
        total=total,
    )

@router.get("/monthly-total", response_model=MonthlyTotalResponse)
def get_monthly_total(
    user_id: int = Query(..., description="Kullanıcı id"),
    year: int = Query(..., ge=2000, le=2100),
    month: int = Query(..., ge=1, le=12),
    db: Session = Depends(get_db),
):
    """
    Belirtilen ay için kullanıcının toplam harcamasını ve adetini döner.
    Not: Bu endpoint dinamik /{expense_id} rotasından ÖNCE tanımlanmalıdır.
    """
    result = (
        db.query(
            func.coalesce(func.sum(Expense.amount), 0).label("total_amount"),
            func.count(Expense.id).label("expense_count"),
        )
        .filter(Expense.user_id == user_id)
        .filter(extract("year", Expense.expense_date) == year)
        .filter(extract("month", Expense.expense_date) == month)
        .first()
    )
    total_amount = Decimal(str(result.total_amount)) if result else Decimal("0")
    count = result.expense_count if result else 0
    return MonthlyTotalResponse(
        user_id=user_id,
        year=year,
        month=month,
        total_amount=total_amount,
        expense_count=count,
    )

@router.get("/{expense_id}", response_model=ExpenseResponse)
def get_expense(
    expense_id: int,
    user_id: int = Query(..., description="Kullanıcı id (Laravel session)"),
    db: Session = Depends(get_db),
):
    """Tek bir harcamayı döner (kullanıcıya ait olmalı)."""
    exp = (
        db.query(Expense)
        .options(joinedload(Expense.category))
        .filter(Expense.id == expense_id, Expense.user_id == user_id)
        .first()
    )
    if not exp:
        raise HTTPException(status_code=404, detail="Expense not found.")
    return expense_to_response(exp)


@router.put("/{expense_id}", response_model=ExpenseResponse)
def update_expense(
    expense_id: int,
    data: ExpenseUpdate,
    user_id: int = Query(..., description="Kullanıcı id (Laravel session)"),
    db: Session = Depends(get_db),
):
    """Harcama günceller (kullanıcıya ait olmalı)."""
    exp = db.query(Expense).filter(Expense.id == expense_id, Expense.user_id == user_id).first()
    if not exp:
        raise HTTPException(status_code=404, detail="Expense not found.")

    if data.category_id is not None:
        category = db.query(ExpenseCategory).filter(ExpenseCategory.id == data.category_id).first()
        if not category:
            raise HTTPException(status_code=400, detail="Invalid category id.")
        exp.category_id = data.category_id
        exp.category = category

    if data.amount is not None:
        exp.amount = data.amount
    if data.description is not None:
        exp.description = data.description
    if data.expense_date is not None:
        if data.expense_date > date.today():
            raise HTTPException(status_code=400, detail="Expense date cannot be in the future.")
        exp.expense_date = data.expense_date

    db.commit()
    db.refresh(exp)
    # category lazy-load olmasın
    exp = (
        db.query(Expense)
        .options(joinedload(Expense.category))
        .filter(Expense.id == expense_id, Expense.user_id == user_id)
        .first()
    )
    return expense_to_response(exp)


@router.delete("/{expense_id}")
def delete_expense(
    expense_id: int,
    user_id: int = Query(..., description="Kullanıcı id (Laravel session)"),
    db: Session = Depends(get_db),
):
    """Harcama siler (kullanıcıya ait olmalı)."""
    exp = db.query(Expense).filter(Expense.id == expense_id, Expense.user_id == user_id).first()
    if not exp:
        raise HTTPException(status_code=404, detail="Expense not found.")
    db.delete(exp)
    db.commit()
    return {"status": "ok", "deleted_id": expense_id}
