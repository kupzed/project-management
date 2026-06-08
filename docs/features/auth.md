# Feature: Auth

## Deskripsi

Modul autentikasi menyediakan halaman login dan register untuk mengakses aplikasi. Auth menggunakan JWT (JSON Web Token).

## Route

- Frontend: `/auth/login`, `/auth/register`

## Komponen Utama

- `src/routes/auth/+layout.svelte` — Layout auth (tanpa sidebar)
- `src/routes/auth/login/+page.svelte` — Halaman login
- `src/routes/auth/login/_components/` — Form login
- `src/routes/auth/register/+page.svelte` — Halaman register
- `src/routes/auth/register/_components/` — Form register

## Alur Detail

Lihat dokumentasi [JWT Auth](../jwt-auth.md) untuk penjelasan lengkap mengenai:
- Login flow
- Register flow
- Token refresh flow
- Logout flow
- Protected route
- Update profile
- Change password

## UI Behavior

- Halaman auth ditampilkan **tanpa sidebar dan topnav** (layout terpisah).
- Setelah login berhasil, user di-redirect ke dashboard atau URL yang disimpan di query parameter `redirect`.
- Jika user sudah memiliki token dan mengakses halaman auth, tetap bisa masuk (tidak ada auto-redirect ke dashboard dari halaman auth).

## Status

✅ Aktif — Login dan register sudah berfungsi penuh.

### Fitur yang Belum Ada

- Reset password via email
- Email verification
- Social login (Google OAuth, dll)
- Remember me (persistent session)
