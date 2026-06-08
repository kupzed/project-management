# Folder Structure

## Root

```
project-management/
├── backend/               # Laravel 13 API backend
├── frontend/              # SvelteKit 5 frontend SPA
├── LICENSE                # MIT License
└── README.md              # Entry point dokumentasi
```

## Backend (Laravel 13)

```
backend/
├── app/
│   ├── Console/           # Artisan commands
│   ├── Helpers/
│   │   └── ActivityHelper.php     # Helper untuk activity logging
│   ├── Http/
│   │   ├── Controllers/           # API Controllers (15 file)
│   │   ├── Middleware/
│   │   │   ├── LogUserActivity.php        # Middleware audit log per-route
│   │   │   └── SlidingWindowThrottle.php  # Custom rate limiter
│   │   ├── Requests/              # Form Request validation (10 file)
│   │   └── Resources/            # API Resource transformers (11 file)
│   ├── Models/
│   │   ├── Traits/
│   │   │   └── LogsActivity.php   # Trait auto-logging model events
│   │   ├── Activity.php
│   │   ├── ActivityAttachment.php
│   │   ├── BarangCertificate.php
│   │   ├── Category.php
│   │   ├── Certificate.php
│   │   ├── CertificateAttachment.php
│   │   ├── Inventory.php
│   │   ├── Item.php
│   │   ├── Mitra.php              # Tabel: partners
│   │   ├── Project.php
│   │   ├── ProjectMaterial.php
│   │   ├── StockMovement.php      # Immutable (no update/delete)
│   │   ├── User.php
│   │   └── Warehouse.php
│   ├── Providers/
│   └── Services/                  # Business logic layer (12 file)
│       ├── AIDocumentExtractionService.php
│       ├── ActivityLogService.php
│       ├── ActivityService.php
│       ├── AuthService.php
│       ├── BarangCertificateService.php
│       ├── CategoryService.php
│       ├── CertificateService.php
│       ├── FinanceService.php
│       ├── ItemService.php
│       ├── MitraService.php
│       ├── ProjectService.php
│       └── WarehouseService.php
├── config/                # Konfigurasi Laravel
│   ├── auth.php           # Guard JWT sebagai default
│   ├── cors.php           # CORS allow all origins
│   ├── jwt.php            # JWT configuration
│   └── permission.php     # Spatie Permission config
├── database/
│   ├── factories/
│   ├── migrations/        # 18 file migration
│   └── seeders/           # 9 seeder termasuk RolePermissionSeeder
├── routes/
│   ├── api.php            # Semua REST API routes
│   ├── console.php
│   └── web.php
├── storage/               # File uploads & logs
├── .env.example
├── composer.json
└── phpunit.xml
```

## Frontend (SvelteKit 5)

```
frontend/
├── src/
│   ├── app.css                    # Global styles (Tailwind + custom CSS)
│   ├── app.d.ts                   # TypeScript declarations
│   ├── app.html                   # HTML template
│   ├── lib/
│   │   ├── axiosClient.ts         # Axios instance + JWT interceptors
│   │   ├── config.ts              # API & Storage base URL config
│   │   ├── inventory.ts           # Inventory helper utilities
│   │   ├── components/
│   │   │   ├── common/            # ConfirmDialog, dll
│   │   │   ├── detail/            # Detail view components
│   │   │   ├── form/              # Form components
│   │   │   ├── layout/            # Sidebar, TopNav, MobileSidebar, dll
│   │   │   ├── ui/                # UI primitives
│   │   │   ├── Drawer.svelte
│   │   │   ├── FileAttachment.svelte
│   │   │   ├── Modal.svelte
│   │   │   └── Pagination.svelte
│   │   ├── composables/           # Reusable logic (placeholder)
│   │   ├── constants/
│   │   │   ├── activity.ts        # Enum/options aktivitas
│   │   │   ├── certificate.ts     # Enum/options sertifikat
│   │   │   └── project.ts         # Enum/options proyek
│   │   ├── services/              # API service layer (11 file)
│   │   │   ├── authService.ts
│   │   │   ├── dashboardService.ts
│   │   │   ├── projectService.ts
│   │   │   ├── activityService.ts
│   │   │   ├── mitraService.ts
│   │   │   ├── certificateService.ts
│   │   │   ├── barangCertificateService.ts
│   │   │   ├── financeService.ts
│   │   │   ├── inventoryService.ts
│   │   │   └── settingsService.ts
│   │   ├── stores/                # Svelte stores
│   │   │   ├── user.ts            # Current user state
│   │   │   ├── permissions.ts     # User permissions & roles
│   │   │   └── theme.ts           # Dark/light theme
│   │   ├── types/                 # TypeScript type definitions
│   │   │   ├── activity.ts
│   │   │   ├── attachment.ts
│   │   │   ├── barang-certificate.ts
│   │   │   ├── certificate.ts
│   │   │   ├── common.ts
│   │   │   ├── mitra.ts
│   │   │   └── project.ts
│   │   └── utils/                 # Utility functions
│   │       ├── badges.ts          # Badge styling helpers
│   │       ├── errors.ts          # Error handling
│   │       ├── form-data.ts       # FormData builder
│   │       ├── formatters.ts      # Tanggal, angka, mata uang
│   │       ├── scroll-lock.ts     # Scroll lock untuk modal
│   │       ├── toast.ts           # SweetAlert2 wrapper
│   │       └── url.ts             # URL utility
│   └── routes/                    # SvelteKit pages
│       ├── +layout.svelte         # Root layout (sidebar + auth guard)
│       ├── +layout.ts             # Auth check (redirect jika no token)
│       ├── +page.svelte           # Root redirect
│       ├── auth/
│       │   ├── +layout.svelte     # Auth layout (tanpa sidebar)
│       │   ├── login/
│       │   └── register/
│       ├── dashboard/
│       ├── projects/
│       ├── activities/
│       ├── mitras/
│       ├── barang-certificates/
│       ├── certificates/
│       ├── finance/
│       ├── categories/
│       ├── warehouses/
│       ├── items/
│       ├── stock-movements/
│       └── settings/
├── static/                        # Static assets
├── .env.example
├── eslint.config.js
├── package.json
├── svelte.config.js
├── tsconfig.json
└── vite.config.ts
```

## Aturan Penempatan File

### Backend

| Jenis File        | Lokasi                          | Keterangan                          |
| ------------------ | ------------------------------- | ----------------------------------- |
| Controller         | `app/Http/Controllers/`        | Satu controller per resource        |
| Form Request       | `app/Http/Requests/`           | Validasi input per-resource         |
| API Resource       | `app/Http/Resources/`          | Transformasi response JSON          |
| Model              | `app/Models/`                  | Eloquent model + relations + scope  |
| Trait              | `app/Models/Traits/`           | Reusable model behavior             |
| Service            | `app/Services/`                | Business logic, dipanggil controller|
| Middleware          | `app/Http/Middleware/`         | Request/response middleware         |
| Migration          | `database/migrations/`         | Schema changes (sequential)         |
| Seeder             | `database/seeders/`            | Data seeding                        |

### Frontend

| Jenis File        | Lokasi                          | Keterangan                          |
| ------------------ | ------------------------------- | ----------------------------------- |
| Page               | `src/routes/`                  | SvelteKit file-based routing        |
| Layout             | `src/routes/+layout.svelte`    | Shared layout per segment           |
| Component          | `src/lib/components/`          | Reusable UI components              |
| Service            | `src/lib/services/`            | API call functions                  |
| Store              | `src/lib/stores/`              | Svelte writable stores              |
| Type               | `src/lib/types/`               | TypeScript interfaces               |
| Utility            | `src/lib/utils/`               | Helper functions                    |
| Constant           | `src/lib/constants/`           | Enum values & static options        |
