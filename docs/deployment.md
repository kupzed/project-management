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

---

## Docker Deployment

### Arsitektur

```
                          ┌────────────────────────────────────────────┐
   Internet               │  VPS Ubuntu 24.04                          │
   (port 80)              │                                            │
  ──────────────────────► │  ┌──────────┐                              │
                          │  │  Nginx   │──── /api/* ───► PHP-FPM:9000 │
                          │  │  :80     │──── /storage/* ► Volume      │
                          │  │          │──── /* ───────► Node.js:3000 │
                          │  └──────────┘                              │
                          │       │            ┌──────────┐            │
                          │       └───────────►│  MySQL   │            │
                          │                    │  :3306   │            │
                          │                    └──────────┘            │
                          └────────────────────────────────────────────┘
```

### File Docker

| File                        | Deskripsi                                 |
| --------------------------- | ----------------------------------------- |
| `docker-compose.yml`        | Orchestrator semua container              |
| `backend/Dockerfile`        | Image PHP 8.4-FPM untuk Laravel           |
| `frontend/Dockerfile`       | Multi-stage build Node.js untuk SvelteKit |
| `docker/nginx/default.conf` | Konfigurasi Nginx reverse proxy           |
| `docker/mysql/my.cnf`       | Optimasi MySQL untuk 4GB RAM              |
| `.env.docker.example`       | Template environment variable             |

### Prasyarat

- VPS dengan Ubuntu 22.04+ dan minimal 2GB RAM
- Docker Engine dan Docker Compose terinstal
- Git terinstal
- Port 80 terbuka di firewall

### Install Docker di VPS

```bash
# Update sistem
sudo apt update && sudo apt upgrade -y

# Install Docker via official script
curl -fsSL https://get.docker.com | sudo sh

# Tambahkan user ke group docker (agar bisa jalankan tanpa sudo)
sudo usermod -aG docker $USER

# Logout & login ulang agar group aktif
exit
# SSH kembali
```

### Deploy Step-by-Step

```bash
# 1. Clone repository
mkdir -p ~/apps/kupzed && cd ~/apps/kupzed
git clone https://github.com/kupzed/project-management.git
cd project-management

# 2. Setup environment
cp .env.docker.example .env.docker
nano .env.docker    # Edit password & API keys

# 3. Build & jalankan semua container
docker compose up -d --build

# 4. Generate APP_KEY (jalankan sekali saja)
docker compose exec php php artisan key:generate --force

# 5. Jalankan migration
docker compose exec php php artisan migrate --force

# 6. Seed data (opsional)
docker compose exec php php artisan db:seed --force

# 7. Create storage symlink
docker compose exec php php artisan storage:link

# 8. Cache config untuk performa
docker compose exec php php artisan config:cache
docker compose exec php php artisan route:cache
docker compose exec php php artisan view:cache

# 9. Verifikasi
docker compose ps
curl http://localhost
```

### Perintah Maintenance

| Perintah                                      | Fungsi                      |
| --------------------------------------------- | --------------------------- |
| `docker compose up -d`                        | Start semua container       |
| `docker compose down`                         | Stop semua container        |
| `docker compose logs -f`                      | Lihat semua log (live)      |
| `docker compose logs php`                     | Lihat log backend saja      |
| `docker compose logs --tail=50 nginx`         | Lihat 50 log terakhir Nginx |
| `docker compose restart php`                  | Restart backend saja        |
| `docker compose up -d --build`                | Rebuild & restart semua     |
| `docker compose exec php php artisan migrate` | Jalankan migration baru     |
| `docker compose exec php php artisan tinker`  | Buka Laravel REPL           |
| `docker compose exec mysql mysql -u root -p`  | Akses MySQL CLI             |

### Update Deployment

```bash
cd ~/apps/kupzed/project-management

# Tarik perubahan terbaru
git pull origin main

# Rebuild & restart
docker compose up -d --build

# Jalankan migration jika ada
docker compose exec php php artisan migrate --force

# Clear & rebuild cache
docker compose exec php php artisan config:cache
docker compose exec php php artisan route:cache
docker compose exec php php artisan view:cache
```

### Backup Database

```bash
# Backup
docker compose exec mysql mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" indogreen > backup_$(date +%Y%m%d).sql

# Restore
docker compose exec -T mysql mysql -u root -p"$MYSQL_ROOT_PASSWORD" indogreen < backup_20260804.sql
```
