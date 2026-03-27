"""
Personal Finance Tracker - FastAPI Service Layer
Tüm iş mantığı ve MySQL erişimi bu katmanda.
Laravel sadece HTTP istekleri ile bu API'yi kullanır.
"""
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.routers import expenses, categories, auth
from app.database import Base, engine, SessionLocal
import app.models_db  # noqa: F401  (Base metadata'ya modelleri kaydetmek için)

app = FastAPI(
    title="Personal Finance Tracker API",
    description="Harcama CRUD ve aylık özet. Laravel web katmanı bu API'yi kullanır.",
    version="1.0.0",
)

# Laravel farklı portta çalışacağı için CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://127.0.0.1:8000", "http://localhost:8000"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(expenses.router, prefix="/api")
app.include_router(categories.router, prefix="/api")
app.include_router(auth.router, prefix="/api")

DEFAULT_CATEGORIES = [
    "Yemek",
    "Ulaşım",
    "Kira",
    "Faturalar",
    "Market",
    "Sağlık",
    "Eğitim",
    "Eğlence",
    "Giyim",
    "Diğer",
]


@app.on_event("startup")
def startup_init_db():
    """
    Uygulama açılışında MySQL tablolarını oluşturur (yoksa) ve varsayılan kategorileri ekler.
    Alembic/migration kullanılmayan basit kurulum senaryosu için.
    """
    Base.metadata.create_all(bind=engine)

    from app.models_db import ExpenseCategory

    db = SessionLocal()
    try:
        existing = db.query(ExpenseCategory).count()
        if existing == 0:
            db.add_all([ExpenseCategory(name=name) for name in DEFAULT_CATEGORIES])
            db.commit()
    finally:
        db.close()


@app.get("/")
def root():
    return {"service": "Finance Tracker API", "docs": "/docs"}


@app.get("/api/health")
def health():
    return {"status": "ok"}


@app.get("/api/health/db")
def health_db():
    """
    MySQL bağlantısını dener. Hata varsa gerçek hata mesajını döner (sorun tespiti için).
    Tarayıcıda http://127.0.0.1:8001/api/health/db açarak kontrol edin.
    """
    try:
        from sqlalchemy import text
        from app.database import engine
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        return {"status": "ok", "database": "connected"}
    except Exception as e:
        return {
            "status": "error",
            "database": "failed",
            "message": str(e),
        }
