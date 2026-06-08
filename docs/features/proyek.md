# Feature: Proyek

## Deskripsi

Fitur ini digunakan untuk mengelola proyek energi surya (PLTS dan PJUTS) milik Indogreen. Setiap proyek memiliki relasi ke mitra (customer), kategori sistem, status progres, dan tanggal pelaksanaan.

## Route

- Frontend: `/projects`
- API: `GET|POST /api/projects`, `GET|PUT|DELETE /api/projects/{id}`

## Komponen Utama

- `src/routes/projects/+page.svelte` — Halaman list
- `src/routes/projects/_components/` — Form, detail, filter

## Data Source

- Service: `src/lib/services/projectService.ts`
- Controller: `app/Http/Controllers/ProjectController.php`
- Service: `app/Services/ProjectService.php`

## Database Terkait

- `projects` — Data utama proyek
- `partners` — Relasi mitra (customer)
- `activities` — Aktivitas terkait proyek
- `certificates` — Sertifikat terkait proyek

## Business Rules

1. Nama proyek wajib diisi.
2. Deskripsi wajib diisi.
3. Tanggal mulai (`start_date`) wajib diisi.
4. Tanggal selesai (`finish_date`) opsional.
5. Kategori proyek harus salah satu dari enum: `PLTS Hybrid`, `PLTS Ongrid`, `PLTS Offgrid`, `PJUTS All In One`, `PJUTS Two In One`, `PJUTS Konvensional`.
6. Status proyek: `Ongoing`, `Prospect`, `Complete`, `Cancel`.
7. Mitra (`mitra_id`) opsional, relasi ke tabel `partners`.
8. Flag `is_cert_projects` menandai proyek sertifikasi.
9. Menghapus proyek **tidak** menghapus mitra, tetapi akan cascade delete activities.

## Filter & Sorting

| Filter            | Deskripsi                              |
| ----------------- | -------------------------------------- |
| `status`          | Filter berdasarkan status proyek       |
| `kategori`        | Filter berdasarkan kategori PLTS/PJUTS |
| `customer_id`     | Filter berdasarkan mitra               |
| `is_cert_projects`| Filter proyek sertifikasi              |
| `date_from`       | Tanggal mulai dari                     |
| `date_to`         | Tanggal mulai sampai                   |
| `search`          | Pencarian di nama, deskripsi, lokasi, no_po, no_so, nama mitra |
| `sort_by`         | `created` (default) atau `start_date`  |
| `sort_dir`        | `asc` atau `desc` (default: `desc`)    |

## Status

✅ Aktif — CRUD proyek sudah berfungsi penuh.
