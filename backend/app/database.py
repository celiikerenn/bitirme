"""
MySQL veritabanı bağlantısı - SQLAlchemy engine ve session.
"""
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker, declarative_base

from app.config import settings

# MySQL bağlantısı
engine = create_engine(
    settings.database_url,
    pool_pre_ping=True,
    echo=False,  # SQL logları için True yapılabilir
)

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()


def get_db():
    """Dependency: Her istek için yeni session, iş bitince kapat."""
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
