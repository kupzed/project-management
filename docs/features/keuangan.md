# Feature: Keuangan

## Deskripsi

Fitur keuangan (Finance) menyediakan laporan keuangan per proyek berdasarkan data aktivitas yang memiliki nilai transaksi. Endpoint ini bersifat read-heavy — hanya mendukung view dan update, tanpa create/delete.

## Route

- Frontend: `/finance`
- API: `GET /api/finance`, `PUT /api/finance/{id}`

## Komponen Utama

- `src/routes/finance/+page.svelte` — Halaman keuangan
- `src/routes/finance/_components/` — Komponen terkait

## Data Source

- Service: `src/lib/services/financeService.ts`
- Controller: `app/Http/Controllers/FinanceController.php`
- Service: `app/Services/FinanceService.php`

## Database Terkait

- `activities` — Sumber data nilai transaksi (`value`)
- `projects` — Relasi proyek

## Business Rules

1. Data keuangan dihitung berdasarkan aktivitas per proyek.
2. Hanya mendukung operasi `index` (list) dan `update`.
3. Tidak ada operasi create atau delete — data berasal dari aktivitas.

## Permission

- `finance-view` — Melihat data keuangan
- `finance-update` — Mengubah data keuangan

> **Catatan:** Tidak ada permission `finance-create` dan `finance-delete`.

## Status

✅ Aktif — Fitur keuangan sudah berfungsi.
