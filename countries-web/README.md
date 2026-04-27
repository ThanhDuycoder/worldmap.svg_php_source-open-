# Countries Web

Trang web giới thiệu quốc gia (click trên bản đồ SVG) và lấy dữ liệu từ RestCountries.

## Cấu trúc

- `index.php`: UI + nhúng bản đồ `assets/svg/world-map.svg`
- `assets/js/*`: frontend (map click/hover, search, render)
- `api/*.php`: mini PHP API (`all`, `country`, `search`)
- `services/CountryService.php`: gọi RestCountries + normalize + cache
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
```

