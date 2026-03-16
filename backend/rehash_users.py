"""
Mevcut users tablosundaki PLAINTEXT şifreleri PBKDF2 hash'e çevirir.

KULLANIM:
  cd backend
  python rehash_users.py

Not:
- Eğer şifreler zaten 'pbkdf2_sha256$...' formatındaysa dokunmaz.
"""
from sqlalchemy import text

from app.database import engine
from app.routers.auth import hash_password


def main():
    updated = 0
    skipped = 0
    with engine.begin() as conn:
        rows = conn.execute(text("SELECT id, password FROM users")).fetchall()
        for user_id, password in rows:
            pwd = password or ""
            if isinstance(pwd, bytes):
                pwd = pwd.decode("utf-8", errors="ignore")
            if str(pwd).startswith("pbkdf2_sha256$"):
                skipped += 1
                continue
            new_hash = hash_password(str(pwd))
            conn.execute(
                text("UPDATE users SET password = :p WHERE id = :id"),
                {"p": new_hash, "id": user_id},
            )
            updated += 1

    print(f"Done. Updated: {updated}, Skipped (already hashed): {skipped}")


if __name__ == "__main__":
    main()

