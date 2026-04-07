# 📋 Proxima — Project Management System

> Aplikasi manajemen proyek internal untuk mengelola proyek, aktivitas, mitra/partner, sertifikasi, dan laporan keuangan.

---

## 📖 Deskripsi

**Proxima** adalah aplikasi _Project Management_ berbasis web yang dirancang untuk mengelola siklus hidup proyek secara menyeluruh — mulai dari pendataan proyek, pencatatan aktivitas harian, manajemen mitra (customer & vendor), pelacakan sertifikasi barang, hingga pelaporan keuangan.

Aplikasi ini dibangun dengan arsitektur **terpisah (decoupled)**:

- **Backend** sebagai RESTful API yang menangani bisnis logika, autentikasi, otorisasi, dan manajemen data.
- **Frontend** sebagai Single Page Application (SPA) yang mengonsumsi API dan menyajikan antarmuka pengguna.

### Fitur Utama

- 🗂️ **Manajemen Proyek** — CRUD proyek dengan filter status, kategori, dan pencarian.
- 📝 **Pencatatan Aktivitas** — Dokumentasi aktivitas per-proyek dengan multi-attachment dan AI-powered document extraction.
- 🤝 **Manajemen Mitra** — Database customer, vendor, dan partner dengan kontak detail.
- 📜 **Sertifikasi** — Pelacakan sertifikat barang dan proyek sertifikasi.
- 💰 **Laporan Keuangan** — Ringkasan finansial proyek.
- 📊 **Dashboard** — Ringkasan data proyek dan statistik.
- 🔐 **Role-Based Access Control** — Manajemen role & permission berbasis Spatie.
- 📋 **Activity Log** — Audit trail untuk semua perubahan data.

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────┐     HTTP/JSON      ┌─────────────────────────┐
│                     │ ◄───────────────── │  Frontend (SvelteKit)   │
│  Backend (Laravel)  │ ──────────────────►│  - v1: frontend-v1│
│  REST API + JWT     │     API Response   │  - v2: frontend-v2│
│  Port: 8000         │                    │  Port: 5174 / 5175       │
└─────────────────────┘                    └─────────────────────────┘
         │
         ▼
┌─────────────────────┐
│  Database (MySQL)   │
└─────────────────────┘
```

---

## ⚙️ Tech Stack

### Backend

| Teknologi         | Versi | Keterangan                                           |
| ----------------- | ----- | ---------------------------------------------------- |
| PHP               | ^8.2  | Runtime server                                       |
| Laravel           | ^11.0 | Framework PHP                                        |
| JWT Auth          | ^2.3  | `php-open-source-saver/jwt-auth` — Autentikasi Token |
| Laravel Sanctum   | ^4.0  | API Token (tersedia sebagai alternatif)              |
| Spatie Permission | ^6.23 | Role & Permission Management                         |
| MySQL / SQLite    | —     | Database relasional                                  |
| Composer          | ^2.x  | PHP dependency manager                               |

### Frontend

| Teknologi    | Versi   | Keterangan                   |
| ------------ | ------- | ---------------------------- |
| SvelteKit    | ^2.22.0 | Framework fullstack Svelte   |
| Svelte       | ^5.x    | Reactive UI framework        |
| TypeScript   | ^5.0    | Type-safe JavaScript         |
| Tailwind CSS | ^4.x    | Utility-first CSS framework  |
| Vite         | ^7.x    | Build tool & dev server      |
| Axios        | ^1.10   | HTTP client (v1)             |
| Chart.js     | ^4.5    | Visualisasi data/grafik      |
| SweetAlert2  | ^11.x   | Dialog/notifikasi interaktif |

### Tools Pendukung

| Tool         | Keterangan                    |
| ------------ | ----------------------------- |
| ESLint       | Linting JavaScript/TypeScript |
| Prettier     | Code formatting               |
| Vitest       | Unit testing framework        |
| Playwright   | Browser-based testing         |
| Laravel Pint | PHP code style fixer          |
| PHPUnit      | PHP testing framework         |

---

## 📋 Prerequisites

Pastikan tools berikut sudah terinstall di mesin development Anda:

| Tool     | Versi Minimum        | Cek Versi         |
| -------- | -------------------- | ----------------- |
| PHP      | 8.2+                 | `php -v`          |
| Composer | 2.x                  | `composer -V`     |
| Node.js  | 18+ (disarankan 20+) | `node -v`         |
| NPM      | 9+                   | `npm -v`          |
| MySQL    | 8.0+ (atau SQLite)   | `mysql --version` |
| Git      | 2.x                  | `git --version`   |

> [!TIP]
> Untuk pengguna Windows, **[Laragon](https://laragon.org/)** sangat direkomendasikan karena sudah mencakup PHP, MySQL, Apache/Nginx, dan Composer dalam satu paket.

---

## 🚀 Installation & Setup Guide

### 1. Clone Repository

```bash
# Project Management
git clone [https://github.com/indogreen/project-management.git]
cd project-management

# Laravel Backend (pada terminal terpisah)
cd backend

# SvelteKit Frontend v1 (pada terminal terpisah)
cd frontend-v1

# SvelteKit Frontend v2 (pada terminal terpisah)
cd frontend-v2
```

---

### 2. Setup Backend (Laravel)

```bash
cd backend

# Install dependencies PHP
composer install

# Salin file environment
copy .env.example .env        # Windows
# cp .env.example .env        # Linux/Mac

# Generate application key
php artisan key:generate

# Generate JWT secret key
php artisan jwt:secret
```

#### Konfigurasi `.env` Backend

Buka file `.env` dan sesuaikan konfigurasi berikut:

```env
# ── Aplikasi ────────────────────────────────────────
APP_NAME="Proxima"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# ── Database ────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=[nama_database]
DB_USERNAME=root
DB_PASSWORD=

# ── JWT ─────────────────────────────────────────────
JWT_SECRET=[akan terisi otomatis setelah php artisan jwt:secret]

# ── AI Document Extraction (Opsional) ──────────────
AI_BASE_URL=[url_ai_provider]
AI_API_KEY=[api_key_anda]
AI_MODEL=[nama_model]
```

#### Jalankan Migration & Seeder

```bash
# Buat tabel di database
php artisan migrate

# (Opsional) Jalankan seeder untuk data awal
php artisan db:seed

# Buat symbolic link untuk storage publik
php artisan storage:link
```

#### Jalankan Backend Server

```bash
# Cara 1: Laravel dev server saja
php artisan serve

# Cara 2: Jalankan semua service sekaligus (server + queue + logs + vite)
composer run dev
```

Server backend akan berjalan di: `http://localhost:8000`

---

### 3. Setup Frontend (SvelteKit)

> [!NOTE]
> Langkah ini sama untuk **v1** (`frontend-v1`) maupun **v2** (`frontend-v2`).

```bash
cd frontend-v1    # atau frontend-v2

# Install dependencies Node.js
npm install

# Salin file environment
copy .env.example .env        # Windows
# cp .env.example .env        # Linux/Mac
```

#### Konfigurasi `.env` Frontend

```env
# Server development
DEV_HOST=localhost
DEV_PORT=5174              # Gunakan port berbeda untuk v2 (misal: 5175)

# Server preview
PREVIEW_HOST=localhost
PREVIEW_PORT=4174

# HMR (Hot Module Replacement)
HMR_HOST=localhost
HMR_PORT=24679             # Gunakan port berbeda untuk v2

# Public — URL Backend API
PUBLIC_API_BASE_URL=http://127.0.0.1:8000/api
PUBLIC_STORAGE_BASE_URL=http://127.0.0.1:8000/storage
```

#### Jalankan Frontend Dev Server

```bash
npm run dev
```

Frontend akan berjalan di: `http://localhost:5174` (atau port yang dikonfigurasi)

---

### 4. Verifikasi Instalasi

| Checklist      | URL                                    | Expected      |
| -------------- | -------------------------------------- | ------------- |
| ✅ Backend API | `http://localhost:8000/api/auth/login` | Response JSON |
| ✅ Frontend v1 | `http://localhost:5174`                | Halaman Login |
| ✅ Frontend v2 | `http://localhost:5175`                | Halaman Login |

---

## 📁 Struktur Repository

```
[project-root]/
├── backend/        # Laravel REST API
├── frontend-v1/    # SvelteKit Frontend v1 (Axios-based)
└── frontend-v2/    # SvelteKit Frontend v2 (Fetch-based)
```

> [!IMPORTANT]
> Setiap repository memiliki `.git` sendiri. Pastikan untuk mengelola version control secara independen per-repository.

---

## 🧪 Testing

```bash
# Backend — PHPUnit
cd backend
php artisan test

# Frontend — Vitest
cd frontend-v1      # atau frontend-v2
npm run test
```

---

## 📚 Dokumentasi Lanjutan

| Dokumen                                                  | Deskripsi                            |
| -------------------------------------------------------- | ------------------------------------ |
| [API Documentation](./docs/API_DOCUMENTATION.md)         | Template & referensi endpoint API    |
| [Frontend Architecture](./docs/FRONTEND_ARCHITECTURE.md) | Arsitektur & pola frontend SvelteKit |
| [Database Structure](./docs/DATABASE_STRUCTURE.md)       | Skema tabel & relasi database        |
| [Code Standards & Changelog](./docs/CODE_STANDARDS.md)   | Standar kode & catatan perubahan     |

---

## 👥 Tim & Kontributor

| Nama   | Role                | Github                      |
| ------ | ------------------- | --------------------------- |
| Kupzed | Fullstack Developer | [https://github.com/kupzed] |
