# Feature: Mitra

## Deskripsi

Fitur ini digunakan untuk mengelola data mitra bisnis Indogreen, baik sebagai customer maupun vendor. Satu mitra bisa memiliki lebih dari satu flag secara bersamaan (contoh: sekaligus customer dan vendor).

## Route

- Frontend: `/mitras`
- API: `GET|POST /api/mitras`, `GET|PUT|DELETE /api/mitras/{id}`

## Komponen Utama

- `src/routes/mitras/+page.svelte` — Halaman list
- `src/routes/mitras/_components/` — Form, detail, filter

## Data Source

- Service: `src/lib/services/mitraService.ts`
- Controller: `app/Http/Controllers/MitraController.php`
- Service: `app/Services/MitraService.php`
- Model: `app/Models/Mitra.php` (tabel: `partners`)

## Database Terkait

- `partners` — Data utama mitra
- `projects` — Proyek yang dimiliki mitra
- `barang_certificates` — Barang sertifikat mitra

## Business Rules

1. Nama mitra (`nama`) wajib diisi.
2. Alamat wajib diisi.
3. Mitra memiliki 4 flag boolean: `is_pribadi`, `is_perusahaan`, `is_customer`, `is_vendor`.
4. Satu mitra bisa memiliki kombinasi flag (contoh: perusahaan yang sekaligus customer dan vendor).
5. Mendukung dua kontak person per mitra (nama, nomor, jabatan).
6. Website dan email opsional.
7. Menghapus mitra akan set NULL pada `mitra_id` di tabel `projects` dan `activities`.

## Filter & Sorting

| Filter        | Deskripsi                                         |
| ------------- | ------------------------------------------------- |
| `kategori`    | Filter: `pribadi`, `perusahaan`, `customer`, `vendor` |
| `date_from`   | Tanggal dibuat dari                               |
| `date_to`     | Tanggal dibuat sampai                             |
| `search`      | Pencarian di nama, email, alamat, website         |

## Status

✅ Aktif — CRUD mitra sudah berfungsi penuh.
