# Environment Variables

## Backend (Laravel)

Lokasi file: `backend/.env` (salin dari `backend/.env.example`)

### Core Application

| Variable              | Scope  | Required | Default         | Deskripsi                              |
| --------------------- | ------ | -------- | --------------- | -------------------------------------- |
| `APP_NAME`            | Server | Yes      | `Laravel`       | Nama aplikasi                          |
| `APP_ENV`             | Server | Yes      | `local`         | Environment: `local`, `production`     |
| `APP_KEY`             | Server | Yes      | —               | Encryption key (generate via artisan)  |
| `APP_DEBUG`           | Server | Yes      | `true`          | Debug mode, `false` di production      |
| `APP_URL`             | Server | Yes      | `http://localhost` | Base URL aplikasi backend           |

### Database

| Variable              | Scope  | Required | Default         | Deskripsi                              |
| --------------------- | ------ | -------- | --------------- | -------------------------------------- |
| `DB_CONNECTION`       | Server | Yes      | `sqlite`        | Driver database (`mysql` di production)|
| `DB_HOST`             | Server | Yes      | `127.0.0.1`     | Host database                          |
| `DB_PORT`             | Server | Yes      | `3306`          | Port database                          |
| `DB_DATABASE`         | Server | Yes      | `laravel`       | Nama database                          |
| `DB_USERNAME`         | Server | Yes      | `root`          | Username database                      |
| `DB_PASSWORD`         | Server | Yes      | —               | Password database                      |

### JWT Authentication

| Variable              | Scope  | Required | Default         | Deskripsi                              |
| --------------------- | ------ | -------- | --------------- | -------------------------------------- |
| `JWT_SECRET`          | Server | Yes      | —               | Secret key JWT (generate via artisan)  |
| `JWT_ALGO`            | Server | No       | `HS256`         | Algoritma signing JWT                  |

### AI Integration

| Variable              | Scope  | Required | Default         | Deskripsi                              |
| --------------------- | ------ | -------- | --------------- | -------------------------------------- |
| `AI_DRIVER`           | Server | No       | —               | Driver AI (contoh: `gemini`)           |
| `AI_BASE_URL`         | Server | No       | —               | Base URL API provider AI               |
| `AI_API_KEY`          | Server | No       | —               | API key provider AI                    |
| `AI_MODEL`            | Server | No       | —               | Model AI yang digunakan                |

### Server

| Variable              | Scope  | Required | Default         | Deskripsi                              |
| --------------------- | ------ | -------- | --------------- | -------------------------------------- |
| `SERVER_HOST`         | Server | No       | `127.0.0.1`     | Host untuk `artisan serve`             |
| `SERVER_PORT`         | Server | No       | `8000`          | Port untuk `artisan serve`             |

### Lainnya

| Variable              | Scope  | Required | Default         | Deskripsi                              |
| --------------------- | ------ | -------- | --------------- | -------------------------------------- |
| `SESSION_DRIVER`      | Server | No       | `database`      | Driver session                         |
| `QUEUE_CONNECTION`    | Server | No       | `database`      | Driver queue                           |
| `CACHE_STORE`         | Server | No       | `database`      | Driver cache                           |
| `FILESYSTEM_DISK`     | Server | No       | `local`         | Default storage disk                   |
| `BCRYPT_ROUNDS`       | Server | No       | `12`            | Bcrypt hashing rounds                  |

---

## Frontend (SvelteKit)

Lokasi file: `frontend/.env` (salin dari `frontend/.env.example`)

### Public (terekspos ke browser)

| Variable                  | Scope  | Required | Default                         | Deskripsi                         |
| ------------------------- | ------ | -------- | ------------------------------- | --------------------------------- |
| `PUBLIC_API_BASE_URL`     | Public | Yes      | `http://127.0.0.1:8000/api`    | Base URL API backend              |
| `PUBLIC_STORAGE_BASE_URL` | Public | Yes      | `http://127.0.0.1:8000/storage`| Base URL file storage backend     |

### Development Server

| Variable         | Scope  | Required | Default     | Deskripsi                              |
| ---------------- | ------ | -------- | ----------- | -------------------------------------- |
| `DEV_HOST`       | Server | No       | `localhost` | Host untuk dev server                  |
| `DEV_PORT`       | Server | No       | `5174`      | Port untuk dev server                  |
| `PREVIEW_HOST`   | Server | No       | `localhost` | Host untuk preview server              |
| `PREVIEW_PORT`   | Server | No       | `4174`      | Port untuk preview server              |
| `HMR_HOST`       | Server | No       | `localhost` | Host untuk Hot Module Replacement      |
| `HMR_PORT`       | Server | No       | `24679`     | Port untuk Hot Module Replacement      |

---

## Catatan Keamanan

> ⚠️ **PENTING:**
> - Jangan pernah commit file `.env` ke repository.
> - File `.env.example` TIDAK BOLEH berisi secret asli.
> - `JWT_SECRET`, `APP_KEY`, `AI_API_KEY`, dan `DB_PASSWORD` adalah **rahasia** — generate per environment.
> - Di production, set `APP_DEBUG=false` untuk mencegah kebocoran informasi.
> - Variable `PUBLIC_*` di frontend terekspos ke browser — jangan simpan secret di sini.
