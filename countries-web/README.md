# Countries Web

Trang web giới thiệu quốc gia (click trên bản đồ SVG) và lấy dữ liệu từ RestCountries.

## Cấu trúc

- `index.php`: UI + nhúng bản đồ `assets/svg/world-map.svg`
- `assets/js/*`: frontend (map click/hover, search, render)
- `api/*.php`: mini PHP API (`all`, `country`, `search`, `chat`)
- `services/CountryService.php`: gọi RestCountries + normalize + cache
- `services/GeminiService.php`: gọi Gemini API để trả lời câu hỏi về quốc gia
- `config/prompts.php`: cấu hình prompt system cho chatbot Gemini
- `cache/countries.json`: cache file

## Chạy

Bạn cần PHP 8+.

### Cách 1: PHP built-in server

```bash
cd countries-web
php -S localhost:8000
```

Mở `http://localhost:8000`.

### Cách 2: XAMPP/WAMP

Copy folder `countries-web/` vào web root và mở `index.php`.

## Config (optional)

Tạo file `.env` trong `countries-web/`:

```env
RESTCOUNTRIES_BASE_URL=https://restcountries.com/v3.1
GEMINI_API_KEY=your_gemini_api_key
GEMINI_MODEL=gemini-1.5-flash-latest
```

## Auth + SQL + OAuth

Du an da co san:

- `pages/auth/index.php`: giao dien dang nhap / dang ky
- `api/auth/register.php`, `api/auth/login.php`, `api/auth/logout.php`, `api/auth/me.php`
- `api/oauth/google/start.php`, `api/oauth/google/callback.php`
- `database.sql`: script tao DB + bang `users`

### 1) Tao database

Chay file `database.sql` trong MySQL (phpMyAdmin hoac CLI).

### 2) Cau hinh `.env`

Them vao `.env`:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=countries_web
DB_USER=root
DB_PASS=

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost/countries-web/api/oauth/google/callback.php
```

### 3) Cau hinh Google OAuth Console

- Authorized redirect URI phai dung voi `GOOGLE_REDIRECT_URI`.
- Vi du XAMPP: `http://localhost/countries-web/api/oauth/google/callback.php`.

