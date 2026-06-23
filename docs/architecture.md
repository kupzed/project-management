# Architecture

## High-Level Architecture

Aplikasi ini menggunakan arsitektur **separated fullstack** dengan frontend dan backend sebagai dua aplikasi terpisah yang berkomunikasi melalui REST API.

```
┌─────────────────────┐          REST API (JSON)         ┌──────────────────────┐
│                     │ ◄──────────────────────────────► │                      │
│   SvelteKit 5       │         JWT Bearer Token         │   Laravel 13         │
│   (Frontend SPA)    │                                  │   (API Backend)      │
│                     │                                  │                      │
│   Port: 5174        │                                  │   Port: 8000/8001    │
│   Tailwind CSS 4    │                                  │   PHP 8.4            │
│   Chart.js          │                                  │   Spatie Permission  │
│   Axios             │                                  │   JWT Auth           │
│   SweetAlert2       │                                  │   AI Integration     │
│                     │                                  │                      │
└─────────────────────┘                                  └──────────┬───────────┘
                                                                    │
                                                                    │ Eloquent ORM
                                                                    ▼
                                                         ┌──────────────────────┐
                                                         │                      │
                                                         │   MySQL Database     │
                                                         │                      │
                                                         │   + File Storage     │
                                                         │     (local disk)     │
                                                         │                      │
                                                         └──────────────────────┘
```

## Alur Request Frontend → Backend

```
Browser
  │
  ├── SvelteKit Route (+page.svelte)
  │     │
  │     ├── Service Layer (lib/services/*.ts)
  │     │     │
  │     │     └── Axios Client (lib/axiosClient.ts)
  │     │           │
  │     │           ├── Request Interceptor: attach JWT dari localStorage
  │     │           │
  │     │           └── Response Interceptor: silent token refresh on 401
  │     │
  │     └── HTTP Request ke Laravel API
  │
  ▼
Laravel Backend
  │
  ├── Middleware Stack
  │     ├── CORS
  │     ├── SlidingWindowThrottle (rate limiting)
  │     ├── auth:api (JWT validation)
  │     ├── role:xxx (Spatie role check, route tertentu)
  │     └── LogUserActivity (audit logging)
  │
  ├── Controller
  │     ├── Validasi input (Form Request)
  │     └── Panggil Service Layer
  │
  ├── Service Layer
  │     └── Business logic + Eloquent query
  │
  ├── Model Layer
  │     ├── Eloquent Model + Relations
  │     ├── Scope (filter, sort)
  │     └── LogsActivity Trait (auto audit log)
  │
  └── API Resource (transform response)
        │
        └── JSON Response → Browser
```

## Alur Autentikasi JWT

```
┌─────────┐    POST /api/auth/login     ┌─────────┐
│ Browser │ ──────────────────────────►  │ Laravel │
│         │  { email, password }         │         │
│         │                              │         │
│         │  ◄────────────────────────── │         │
│         │  { access_token, expires_in }│         │
│         │                              │         │
│  Store token in localStorage           │         │
│         │                              │         │
│         │    GET /api/auth/me          │         │
│         │ ──────────────────────────►  │         │
│         │  Authorization: Bearer xxx   │         │
│         │                              │         │
│         │  ◄────────────────────────── │         │
│         │  { user, roles, permissions }│         │
└─────────┘                              └─────────┘
```

### Token Refresh Flow

Ketika API mengembalikan **401 Unauthorized**, Axios interceptor otomatis:

1. Mengirim `POST /api/auth/refresh` dengan token lama.
2. Jika berhasil, simpan token baru di `localStorage`.
3. Retry request yang gagal dengan token baru.
4. Jika refresh gagal, hapus token dan redirect ke `/auth/login`.
5. Request lain yang datang selama proses refresh di-queue dan di-resolve setelah token baru didapat.

## Alur Protected Route (Frontend)

```
Browser navigasi ke /projects
  │
  └── +layout.ts (root)
        │
        ├── Pathname dimulai /auth? → Lewatkan, tidak perlu token
        │
        └── Cek localStorage.getItem('jwt_token')
              │
              ├── Token ADA → Load halaman, ambil data user via /auth/me
              │
              └── Token TIDAK ADA → Redirect ke /auth/login?redirect=...
```

> **Catatan:** SSR dimatikan (`ssr = false`). Seluruh aplikasi berjalan sebagai SPA client-side.

## Alur Rate Limiting

Backend menggunakan custom **Sliding Window Throttle** middleware:

```
Request masuk
  │
  ├── User terautentikasi (login)? → YA → Bypass throttle, lanjutkan request
  │
  ├── TIDAK → Counter < limit? → Increment counter, reset TTL, lanjutkan request
  │
  └── Counter >= limit? → Tolak dengan 429 Too Many Requests
                           Return Retry-After header
                           TTL TIDAK di-reset (user harus menunggu)
```

Konfigurasi:

- Auth routes: `5 request / 60 detik` (hanya berlaku jika belum terautentikasi)
- API routes: `15 request / 60 detik` (hanya berlaku jika belum terautentikasi)

## Alur Activity Logging

```
Model Event (created/updated/deleted)
  │
  └── LogsActivity Trait (boot)
        │
        └── ActivityLogService.log()
              │
              └── Simpan ke file JSON
                  storage/app/activity-logs/{user_id}/{Y-m-d}.json
```

## Alur Role-Based Access Control (RBAC)

```
                    ┌─────────────┐
                    │ super_admin │ ── Semua permission
                    └──────┬──────┘
                           │
                    ┌──────┴──────┐
                    │    admin    │ ── Semua kecuali delete
                    └──────┬──────┘
                           │
              ┌────────────┴────────────┐
              │                         │
       ┌──────┴──────┐          ┌──────┴──────┐
       │    staff    │          │    user     │
       └─────────────┘          └─────────────┘
       Permission fleksibel     Permission fleksibel
       per user via admin       per user via admin
```

## Stack Komunikasi

| Layer            | Teknologi                 | Keterangan                         |
| ---------------- | ------------------------- | ---------------------------------- |
| HTTP Client      | Axios                     | Dengan interceptor JWT             |
| API Format       | REST + JSON               | Standard Laravel resource response |
| Auth             | JWT (HS256)               | Disimpan di localStorage           |
| RBAC             | Spatie Laravel Permission | Guard: `api`                       |
| Rate Limiting    | Custom Sliding Window     | Menggunakan Laravel Cache + Lock   |
| Validation       | Laravel Form Requests     | Server-side validation             |
| File Upload      | Laravel Storage (local)   | Disk `public` dengan symlink       |
| Activity Logging | Custom JSON file logger   | Per-user, per-hari                 |
