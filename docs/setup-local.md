# Setup Local Development

## Prasyarat

| Software     | Versi Minimum | Keterangan                   |
| ------------ | ------------- | ---------------------------- |
| PHP          | 8.4           | Dengan ekstensi yang diminta Laravel 13 |
| Composer     | 2.x           | Dependency manager PHP       |
| Node.js      | 18+           | Untuk frontend SvelteKit     |
| npm          | 9+            | Package manager frontend     |
| MySQL        | 8.0+          | Database server              |
| Laragon      | (opsional)    | Local dev environment        |

### Ekstensi PHP yang Dibutuhkan

- `pdo_mysql`
- `mbstring`
- `openssl`
- `tokenizer`
- `xml`
- `ctype`
- `json`
- `bcmath`
- `fileinfo`

## Langkah Setup

### 1. Clone Repository

```bash
git clone <repository-url> project-management
cd project-management
```

### 2. Setup Backend (Laravel)

```bash
cd backend

# Install dependency PHP
composer install

# Salin file environment
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Buat database MySQL
# (buat database secara manual, contoh: 'indogreen')

# Edit .env — sesuaikan koneksi database:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=indogreen
# DB_USERNAME=root
# DB_PASSWORD=

# Jalankan migration
php artisan migrate

# Jalankan seeder (data awal + role & permission)
php artisan migrate --seed

# Buat symlink storage untuk file upload
php artisan storage:link
```

### 3. Setup Frontend (SvelteKit)

```bash
cd frontend

# Install dependency Node.js
npm install

# Salin file environment
cp .env.example .env

# Edit .env jika perlu — sesuaikan URL backend:
# PUBLIC_API_BASE_URL=http://127.0.0.1:8000/api
# PUBLIC_STORAGE_BASE_URL=http://127.0.0.1:8000/storage
```

### 4. Menjalankan Development Server

**Terminal 1 — Backend:**

```bash
cd backend
php artisan serve
# Server berjalan di http://127.0.0.1:8000
```

**Terminal 2 — Frontend:**

```bash
cd frontend
npm run dev
# Server berjalan di http://localhost:5174
```

> **Tips:** Backend juga menyediakan script `composer dev` yang menjalankan server, queue, log viewer, dan Vite sekaligus menggunakan `concurrently`.

### 5. Akses Aplikasi

Buka browser ke `http://localhost:5174`.

Akun default setelah seeding (lihat `UserSeeder`):
- Buat akun via halaman register `/auth/register`
- User pertama akan otomatis menjadi `super_admin` via `RolePermissionSeeder`

## Command Penting

### Backend

| Command                              | Deskripsi                            |
| ------------------------------------ | ------------------------------------ |
| `php artisan serve`                  | Jalankan dev server                  |
| `php artisan migrate`                | Jalankan migration                   |
| `php artisan migrate --seed`         | Migration + seeding                  |
| `php artisan migrate:fresh --seed`   | Reset DB + migration + seeding       |
| `php artisan db:seed`                | Jalankan seeder saja                 |
| `php artisan storage:link`           | Buat symlink public storage          |
| `php artisan jwt:secret`             | Generate JWT secret key              |
| `php artisan key:generate`           | Generate app key                     |
| `php artisan route:list`             | Lihat daftar route                   |
| `php artisan test`                   | Jalankan PHPUnit test                |
| `composer dev`                       | Jalankan semua service sekaligus     |

### Frontend

| Command                | Deskripsi                            |
| ---------------------- | ------------------------------------ |
| `npm run dev`          | Jalankan dev server                  |
| `npm run build`        | Build production                     |
| `npm run preview`      | Preview production build             |
| `npm run lint`         | Jalankan Prettier + ESLint           |
| `npm run format`       | Format kode dengan Prettier          |
| `npm run check`        | Svelte type checking                 |
| `npm run test`         | Jalankan Vitest                      |

## Masalah Umum saat Local Development

### Port sudah dipakai

Jika port 8000 atau 5174 sudah digunakan, edit:
- Backend: `SERVER_PORT` di `.env` atau gunakan `php artisan serve --port=8001`
- Frontend: `DEV_PORT` di `.env`

### CORS error

Pastikan `config/cors.php` di backend mengizinkan origin frontend. Saat ini konfigurasi menggunakan `allowed_origins => ['*']`.

### Akses dari perangkat lain di jaringan lokal

1. Sesuaikan `SERVER_HOST` di backend `.env` ke IP lokal (contoh: `192.168.1.x`).
2. Sesuaikan `DEV_HOST` dan `HMR_HOST` di frontend `.env` ke `0.0.0.0` atau IP lokal.
3. Sesuaikan `PUBLIC_API_BASE_URL` ke IP lokal backend.

### Database connection error

Pastikan MySQL berjalan dan credential di `.env` backend sudah benar. Jika menggunakan Laragon, MySQL biasanya berjalan di port 3306 dengan username `root` tanpa password.
