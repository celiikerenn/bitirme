"""
Kayıt ve giriş - Laravel veritabanına erişmediği için auth FastAPI'de.
Laravel formu gönderir, FastAPI user oluşturur/doğrular, Laravel oturum açar.
"""
import base64
import hashlib
import hmac
import os

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database import get_db
from app.models_db import User
from app.schemas import UserRegister, UserLogin, UserResponse

router = APIRouter(prefix="/auth", tags=["auth"])

PBKDF2_ITERATIONS = 210_000
PBKDF2_SALT_BYTES = 16


def hash_password(password: str) -> str:
    """Şifreyi hash'li sakla (plaintext saklama)."""
    pwd = str(password)
    salt = os.urandom(PBKDF2_SALT_BYTES)
    dk = hashlib.pbkdf2_hmac("sha256", pwd.encode("utf-8"), salt, PBKDF2_ITERATIONS)
    # Format: pbkdf2_sha256$<iterations>$<salt_b64>$<hash_b64>
    return (
        "pbkdf2_sha256$"
        f"{PBKDF2_ITERATIONS}$"
        f"{base64.b64encode(salt).decode('ascii')}$"
        f"{base64.b64encode(dk).decode('ascii')}"
    )


def verify_password(plain: str, hashed: str) -> bool:
    """Şifre doğrulama (PBKDF2)."""
    try:
        algo, iterations_s, salt_b64, hash_b64 = str(hashed).split("$", 3)
        if algo != "pbkdf2_sha256":
            return False
        iterations = int(iterations_s)
        salt = base64.b64decode(salt_b64.encode("ascii"))
        expected = base64.b64decode(hash_b64.encode("ascii"))
        dk = hashlib.pbkdf2_hmac("sha256", str(plain).encode("utf-8"), salt, iterations)
        return hmac.compare_digest(dk, expected)
    except Exception:
        return False


@router.post("/register", response_model=UserResponse)
def register(data: UserRegister, db: Session = Depends(get_db)):
    """Yeni kullanıcı kaydı. Laravel register formundan çağrılır."""
    try:
        if db.query(User).filter(User.email == data.email).first():
            raise HTTPException(status_code=400, detail="This email is already registered.")

        hashed = hash_password(data.password)
        user = User(
            name=data.name,
            email=data.email,
            password=hashed,
            role=data.role,
        )
        db.add(user)
        db.commit()
        db.refresh(user)
        return UserResponse(id=user.id, name=user.name, email=user.email, role=str(user.role))
    except HTTPException:
        raise
    except Exception as e:
        db.rollback()
        # Tüm hataları yakala ve gerçek mesajı göster
        err_msg = str(e)
        if hasattr(e, "orig"):
            err_msg = str(e.orig)
        elif hasattr(e, "__cause__") and e.__cause__:
            err_msg = str(e.__cause__)
        raise HTTPException(status_code=503, detail=f"Veritabanı hatası: {err_msg}") from e


@router.post("/login", response_model=UserResponse)
def login(data: UserLogin, db: Session = Depends(get_db)):
    """E-posta/şifre ile giriş. Başarılıysa kullanıcı bilgisi döner; Laravel session'a yazar."""
    user = db.query(User).filter(User.email == data.email).first()
    if not user or not verify_password(data.password, user.password):
        raise HTTPException(status_code=401, detail="Invalid email or password.")
    return UserResponse(id=user.id, name=user.name, email=user.email, role=str(user.role))
