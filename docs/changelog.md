# Changelog

## [2026-06-24] — feat(inventory): add item attachments, placement, and stock movement CRUD

- Menambahkan fitur lampiran (attachments) untuk master data items/material (file upload, rename, delete).
- Menambahkan field `placement` (rak/lokasi penyimpanan) pada tabel `inventories` dan menampilkan lokasinya di detail drawer gudang serta tabel item.
- Menghapus batasan immutable pada mutasi stok (stock movements) dan mengimplementasikan CRUD (show detail, edit quantity/catatan/waktu, dan delete mutasi dengan penyesuaian stok otomatis).
- File yang diubah:
  - Backend: `database/migrations/2026_06_24_000001_create_item_attachments_table.php`, `database/migrations/2026_06_01_000004_create_inventories_table.php`, `app/Models/ItemAttachment.php`, `app/Models/Item.php`, `app/Models/Inventory.php`, `app/Models/StockMovement.php`, `app/Services/ItemService.php`, `app/Services/WarehouseService.php`, `app/Http/Controllers/ItemController.php`, `app/Http/Controllers/StockMovementController.php`, `app/Http/Requests/ItemRequest.php`, `app/Http/Requests/StockMovementUpdateRequest.php`, `app/Http/Resources/ItemResource.php`, `app/Http/Resources/WarehouseResource.php`, `database/seeders/RolePermissionSeeder.php`, `app/Http/Controllers/RoleController.php`, `routes/api.php`
  - Frontend: `src/lib/inventory.ts`, `src/lib/services/inventoryService.ts`, `src/routes/items/_components/ItemsPageClient.svelte`, `src/routes/items/_components/ItemTable.svelte`, `src/routes/warehouses/_components/WarehousesPageClient.svelte`, `src/routes/stock-movements/_components/stock-movement.ts`, `src/routes/stock-movements/_components/StockMovementsPageClient.svelte`, `src/routes/stock-movements/_components/StockMovementModal.svelte`
- Dampak yang perlu diketahui:
  - Perlu menjalankan `php artisan migrate` atau `php artisan migrate:fresh` untuk memperbarui skema database (tabel `item_attachments` baru dan kolom `placement` pada `inventories`).
  - Mutasi stok sekarang bisa diedit dan didelete, dan akan melakukan adjustment otomatis pada jumlah stok barang di gudang asal/tujuan.

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
