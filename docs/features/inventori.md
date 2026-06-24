# Feature: Inventori

## Deskripsi

Modul inventori mencakup pengelolaan gudang, master data item, dan mutasi stok. Sistem ini menggunakan pola **ledger-based inventory** di mana setiap pergerakan stok dicatat di `stock_movements`, dan stok aktual di-maintain di tabel `inventories`.

## Sub-Modul

### 1. Kategori
- Route: `/categories`
- API: `GET|POST|PUT|DELETE /api/categories`
- Deskripsi: Kategori umum dengan tipe (`item`, `project`, `activity`, `certificate`)
- Constraint: Kombinasi `name` + `type` harus unique

### 2. Gudang (Warehouse)
- Route: `/warehouses`
- API: `GET|POST|PUT|DELETE /api/warehouses`
- Deskripsi: Lokasi penyimpanan barang
- Constraint: Tidak bisa dihapus jika masih memiliki inventori atau riwayat stok

### 3. Item
- Route: `/items`
- API: `GET|POST|PUT|DELETE /api/items`
- Deskripsi: Master data barang dengan SKU unik, satuan, dan minimum stock
- Constraint: SKU harus unique, `category_id` wajib, tidak bisa dihapus jika ada inventori

### 4. Mutasi Stok (Stock Movement)
- Route: `/stock-movements`
- API: `GET /api/stock-movements`, `POST /api/stock-movements/{type}`, `PUT /api/stock-movements/{id}`, `DELETE /api/stock-movements/{id}`
- Deskripsi: Pencatatan pergerakan stok (mendukung CRUD)

## Komponen Utama

- `src/routes/categories/` — Halaman kategori
- `src/routes/warehouses/` — Halaman gudang
- `src/routes/items/` — Halaman item
- `src/routes/stock-movements/` — Halaman mutasi stok

## Data Source

- Service: `src/lib/services/inventoryService.ts`
- Controller: `app/Http/Controllers/CategoryController.php`
- Controller: `app/Http/Controllers/WarehouseController.php`
- Controller: `app/Http/Controllers/ItemController.php`
- Controller: `app/Http/Controllers/StockMovementController.php`
- Service: `app/Services/WarehouseService.php` (business logic utama inventori)

## Database Terkait

- `categories` — Kategori item
- `warehouses` — Gudang
- `items` — Master barang
- `inventories` — Stok aktual per item per gudang
- `stock_movements` — Riwayat mutasi (mendukung edit/delete dengan penyesuaian stok otomatis)
- `project_materials` — Material yang dialokasikan ke proyek

## Tipe Mutasi Stok

| Tipe                 | Deskripsi                              | Source WH | Dest WH | Project |
| -------------------- | -------------------------------------- | --------- | ------- | ------- |
| `inbound`            | Barang masuk ke gudang                 | —         | ✅      | —       |
| `outbound`           | Barang keluar dari gudang              | ✅        | —       | —       |
| `transfer`           | Transfer antar gudang                  | ✅        | ✅      | —       |
| `project_allocation` | Alokasi barang ke proyek dari gudang   | ✅        | —       | ✅      |

## Business Rules

1. **Mutability** — `StockMovement` mendukung edit (quantity, notes, occurred_at, placement) dan delete dengan penyesuaian/reversal stok otomatis di gudang.
2. **Atomic transaction** — Semua operasi mutasi menggunakan `DB::transaction()` dengan pessimistic locking (`lockForUpdate()`).
3. **Stok harus cukup** — Operasi `outbound`, `transfer`, dan `project_allocation` akan gagal jika stok tidak mencukupi (`Insufficient stock`).
4. **Auto-create inventory** — Jika belum ada record `inventories` untuk kombinasi item + warehouse, otomatis dibuat dengan quantity 0.
5. **Transfer validation** — Gudang asal dan tujuan harus berbeda.
6. **Project allocation** — Selain membuat `stock_movement`, juga membuat record `project_materials` sebagai alokasi material proyek.
7. **Warehouse deletion protection** — Gudang tidak bisa dihapus jika masih memiliki inventori atau riwayat mutasi.

## Filter Mutasi Stok

| Filter         | Deskripsi                           |
| -------------- | ----------------------------------- |
| `type`         | Tipe mutasi                         |
| `item_id`      | Filter berdasarkan item             |
| `project_id`   | Filter berdasarkan proyek           |
| `warehouse_id` | Filter berdasarkan gudang (source atau destination) |

## Status

✅ Aktif — Sistem inventori sudah berfungsi penuh.
