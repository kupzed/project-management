# Changelog

## [2026-06-23] — feat(api): bypass rate limiting for authenticated users

- Menghindari rate limiting (SlidingWindowThrottle) ketika user sudah terautentikasi (login), dan tetap menerapkannya jika user belum login.
- File yang diubah:
  - `app/Http/Middleware/SlidingWindowThrottle.php`
  - `tests/Feature/RateLimiterTest.php`
  - `docs/api-reference.md`
  - `docs/jwt-auth.md`
  - `docs/architecture.md`
- Dampak yang perlu diketahui:
  - User terautentikasi tidak akan lagi dibatasi oleh rate limiter pada endpoint API/auth terlindungi.
  - Rate limiter (5 req/60s) tetap membatasi guest pada endpoint register & login.

## [Unreleased]

### Added

- Dokumentasi project lengkap di folder `docs/`.
  - Overview dan arsitektur aplikasi.
  - Database schema dari 18 migration.
  - Panduan database migration.
  - Dokumentasi JWT authentication flow.
  - Dokumentasi role & permission (RBAC).
  - API reference lengkap.
  - Frontend guidelines.
  - Dokumentasi fitur: Dashboard, Proyek, Aktivitas, Mitra, Sertifikat, Keuangan, Inventori, Settings, Auth.
  - Panduan deployment.
  - Security checklist.
  - Troubleshooting guide.
  - AI development rules.
  - Folder structure documentation.
  - Setup local development guide.
  - Environment variables documentation.
- `README.md` root yang ringkas dan mengarahkan ke `docs/`.
