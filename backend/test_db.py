"""
MySQL bağlantısını test et - FastAPI olmadan direkt test.
Çalıştır: python test_db.py
"""
import sys
from app.config import settings
from app.database import engine
from sqlalchemy import text

print("MySQL Bağlantı Testi")
print("=" * 50)
print(f"Host: {settings.MYSQL_HOST}")
print(f"Port: {settings.MYSQL_PORT}")
print(f"User: {settings.MYSQL_USER}")
print(f"Database: {settings.MYSQL_DATABASE}")
print(f"Password: {'*' * len(settings.MYSQL_PASSWORD) if settings.MYSQL_PASSWORD else '(boş)'}")
print(f"Connection URL: mysql+pymysql://{settings.MYSQL_USER}:***@{settings.MYSQL_HOST}:{settings.MYSQL_PORT}/{settings.MYSQL_DATABASE}")
print("=" * 50)

try:
    print("\nBağlantı deneniyor...")
    with engine.connect() as conn:
        result = conn.execute(text("SELECT 1 as test"))
        row = result.fetchone()
        print("✅ BAŞARILI! MySQL'e bağlanıldı.")
        print(f"   Test sorgusu sonucu: {row[0]}")
        
        # Tabloları kontrol et
        print("\nTablolar kontrol ediliyor...")
        result = conn.execute(text("SHOW TABLES"))
        tables = [row[0] for row in result]
        required = ["users", "expense_categories", "expenses"]
        print(f"   Bulunan tablolar: {', '.join(tables) if tables else '(yok)'}")
        for table in required:
            if table in tables:
                print(f"   ✅ {table} - VAR")
            else:
                print(f"   ❌ {table} - YOK (schema.sql çalıştırılmalı)")
except Exception as e:
    print(f"\n❌ HATA: {type(e).__name__}")
    print(f"   Mesaj: {str(e)}")
    if hasattr(e, "orig"):
        print(f"   Orijinal: {str(e.orig)}")
    sys.exit(1)
