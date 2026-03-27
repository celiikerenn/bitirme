# Laravel Kurulum (Kişisel Finans Takip)

Bu klasör tam bir Laravel projesidir. Finans uygulaması için gerekli controller, view ve FastAPI entegrasyonu eklenmiştir.

## 1. Bağımlılıkları yükle

XAMPP PHP kullanıyorsanız **zip** eklentisinin açık olması kurulumu çok hızlandırır:

- `C:\xampp\php\php.ini` dosyasını açın
- Şu satırı bulun: `;extension=zip`
- Başındaki `;` kaldırın: `extension=zip`
- PHP'yi yeniden başlatın (XAMPP Control Panel'den Apache'yi durdurup başlatın)

Sonra proje klasöründe:

```bash
cd C:\Users\Eren\Desktop\bitirme\laravel_app
composer install
```

## 2. Uygulama anahtarı

```bash
php artisan key:generate
```

## 3. Laravel'i çalıştır

```bash
php artisan serve
```

Tarayıcıda: **http://127.0.0.1:8000**

## 4. Ön koşul: FastAPI ve MySQL

- **FastAPI** backend'in çalışıyor olması gerekir: `cd backend` → `uvicorn app.main:app --reload --port 8001`
- **MySQL** (XAMPP) çalışıyor olmalı ve `database/schema.sql` çalıştırılmış olmalı (finance_tracker veritabanı)

`.env` içinde `FASTAPI_URL=http://127.0.0.1:8001` zaten ayarlı. Session ve cache için veritabanı kullanılmıyor (file driver).
