# Deployment

## Overview

Aplikasi ini terdiri dari dua bagian yang perlu di-deploy terpisah:

1. **Backend (Laravel)** — Bisa di-deploy ke VPS, shared hosting, atau cloud service yang mendukung PHP 8.4.
2. **Frontend (SvelteKit)** — Bisa di-deploy ke Vercel, Netlify, Cloudflare Pages, atau VPS.

> **Catatan:** Belum ada konfigurasi Vercel atau deployment platform lain yang terdeteksi di repository. Panduan ini bersifat umum.

## Prasyarat Production

### Backend

- PHP 8.4+
- MySQL 8.0+
- Composer
- Web server (Nginx/Apache) dengan PHP-FPM
- SSL/HTTPS wajib untuk production
- Storage yang writable untuk file upload

### Frontend

- Node.js 18+ (untuk build)
- Adapter SvelteKit yang sesuai target deployment

## Build Command

### Backend

```bash
cd backend
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
php artisan migrate --force
```

### Frontend

```bash
cd frontend
npm ci
npm run build
```

## Environment Production

### Backend `.env`

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

# Generate fresh untuk production
APP_KEY=base64:xxxx
JWT_SECRET=xxxx

# Matikan log verbose
LOG_LEVEL=warning
```

### Frontend `.env`

```env
PUBLIC_API_BASE_URL=https://api.your-domain.com/api
PUBLIC_STORAGE_BASE_URL=https://api.your-domain.com/storage
```

## CORS Configuration

Saat ini `config/cors.php` menggunakan `allowed_origins => ['*']`. Untuk production, **sangat disarankan** membatasi ke domain frontend saja:

```php
'allowed_origins' => ['https://your-frontend-domain.com'],
```

## Pre-deployment Checklist

### Backend

- [ ] `APP_ENV=production` dan `APP_DEBUG=false`
- [ ] `APP_KEY` dan `JWT_SECRET` sudah di-generate (fresh, bukan dari `.env.example`)
- [ ] Database production sudah dibuat dan migration sudah dijalankan
- [ ] `php artisan storage:link` sudah dijalankan
- [ ] CORS sudah dibatasi ke domain frontend
- [ ] File `.env` tidak ter-commit ke repository
- [ ] Build berhasil tanpa error
- [ ] Rate limiting sudah dikonfigurasi dengan tepat
- [ ] Log level di-set ke `warning` atau `error`

### Frontend

- [ ] `PUBLIC_API_BASE_URL` mengarah ke backend production
- [ ] `PUBLIC_STORAGE_BASE_URL` mengarah ke storage production
- [ ] Build berhasil (`npm run build`)
- [ ] Svelte check berhasil (`npm run check`)
- [ ] Lint berhasil (`npm run lint`)
- [ ] SvelteKit adapter sudah dikonfigurasi sesuai target (auto/node/static/vercel)

### Umum

- [ ] HTTPS aktif untuk semua domain
- [ ] DNS sudah dikonfigurasi
- [ ] Backup database production tersedia
- [ ] Monitoring dan error tracking sudah disiapkan

## Post-deployment Checklist

- [ ] Aplikasi bisa diakses melalui browser
- [ ] Login/register berfungsi
- [ ] File upload berfungsi
- [ ] API response tidak menampilkan debug info
- [ ] CORS tidak memblokir request frontend
- [ ] SSL certificate valid
