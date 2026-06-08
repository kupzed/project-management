# Security Checklist

## Laravel / Backend

- [ ] `APP_DEBUG=false` di production.
- [ ] `APP_KEY` dan `JWT_SECRET` di-generate per environment (jangan pakai value dari `.env.example` atau dari repository).
- [ ] File `.env` tidak ter-commit ke Git (sudah ada di `.gitignore`).
- [ ] CORS dibatasi ke domain frontend saja di production (saat ini `allowed_origins => ['*']`).
- [ ] Rate limiting aktif untuk semua endpoint (Sliding Window Throttle).
- [ ] Semua input divalidasi melalui Form Request.
- [ ] Password di-hash menggunakan bcrypt (12 rounds).
- [ ] Password baru harus minimal 8 karakter, mengandung huruf besar dan kecil.

## JWT Token

- [ ] `JWT_SECRET` cukup panjang dan random (generate via `php artisan jwt:secret`).
- [ ] JWT algorithm menggunakan HS256.
- [ ] Token disimpan di `localStorage` — memahami risiko XSS.
- [ ] Token TTL dikonfigurasi dengan tepat (default 60 menit).
- [ ] Refresh token berfungsi untuk perpanjangan session otomatis.
- [ ] Endpoint `/auth/login` dan `/auth/register` memiliki rate limiting ketat (5 req/60s).

## Role & Permission

- [ ] RBAC aktif menggunakan Spatie Laravel Permission.
- [ ] Endpoint manajemen role hanya bisa diakses `super_admin` dan `admin`.
- [ ] Admin tidak bisa mengubah user setingkat atau di atasnya.
- [ ] User tidak bisa mengubah role dirinya sendiri.
- [ ] Permission diperiksa di backend (bukan hanya di frontend).

## File Upload / Storage

- [ ] File upload menggunakan Laravel Storage disk `public`.
- [ ] Symlink `storage:link` sudah dibuat.
- [ ] Pastikan tidak ada file upload yang bisa di-execute sebagai kode (PHP, shell).
- [ ] Validasi MIME type dan ukuran file di backend.

## Frontend

- [ ] Tidak ada secret yang disimpan di variable `PUBLIC_*`.
- [ ] Error message tidak membocorkan detail internal (SQL, stack trace).
- [ ] Axios interceptor menangani 401 dan redirect ke login.
- [ ] Token dihapus dari `localStorage` saat logout atau refresh gagal.

## Database

- [ ] Database credentials tidak di-commit ke repository.
- [ ] Database user production memiliki privilege minimal yang diperlukan.
- [ ] Query menggunakan Eloquent ORM (terproteksi dari SQL injection).
- [ ] Foreign key constraint aktif untuk menjaga integritas data.
- [ ] StockMovement bersifat immutable (tidak bisa diubah/dihapus).

## Deployment

- [ ] HTTPS aktif untuk semua domain.
- [ ] Header keamanan dikonfigurasi (X-Frame-Options, X-Content-Type-Options, dll).
- [ ] Laravel debug bar/telescope dimatikan di production.
- [ ] Log level minimal `warning` di production.

---

## Security Notes

### ⚠️ Temuan yang Perlu Diperhatikan

1. **CORS terlalu permisif** — `config/cors.php` menggunakan `allowed_origins => ['*']`. Di production, ini harus dibatasi ke domain frontend saja.

2. **JWT di localStorage** — Token JWT disimpan di `localStorage`, yang rentan terhadap serangan XSS. Ini adalah trade-off yang umum untuk SPA, tetapi perlu dipastikan tidak ada celah XSS di frontend.

3. **File `.env` di repository** — File `backend/.env` dengan credential asli terdeteksi di repository. Meskipun `.gitignore` sudah mengecualikan `.env`, file ini mungkin sudah ter-commit sebelumnya. **Segera:**
   - Hapus file `.env` dari tracking Git jika sudah ter-commit.
   - Rotate semua secret (APP_KEY, JWT_SECRET, AI_API_KEY, DB password).

4. **AI API Key terekspos** — `AI_API_KEY` di file `.env` berisi key aktual. Pastikan key ini sudah di-rotate.

5. **`supports_credentials` disabled** — CORS `supports_credentials` di-set `false`, yang berarti cookie-based auth tidak akan berfungsi cross-origin. Ini konsisten dengan penggunaan JWT di header.
