# Feature: Dashboard

## Deskripsi

Halaman dashboard menampilkan ringkasan statistik seluruh proyek, tren bulanan, distribusi status dan kategori, serta top customer.

## Route

- Frontend: `/dashboard`
- API: `GET /api/dashboard`

## Komponen Utama

- `src/routes/dashboard/+page.svelte`
- `src/routes/dashboard/_components/` (komponen chart dan statistik)

## Data Source

- Service: `src/lib/services/dashboardService.ts`
- Controller: `app/Http/Controllers/DashboardController.php`

## Database Terkait

- `projects` — Statistik proyek
- `partners` — Top customer
- `certificates` — Statistik sertifikat

## Data yang Ditampilkan

### Kartu Statistik

| Metrik             | Sumber                                    |
| ------------------ | ----------------------------------------- |
| Total Proyek       | `COUNT(*)` dari `projects`                |
| Ongoing            | `projects.status = 'Ongoing'`             |
| Prospect           | `projects.status = 'Prospect'`            |
| Complete           | `projects.status = 'Complete'`            |
| Cancel             | `projects.status = 'Cancel'`              |
| Proyek Sertifikasi | `projects.is_cert_projects = true`        |
| Sertifikat Aktif   | `certificates.status = 'Aktif'`           |
| Sertifikat Expiring| Sertifikat yang expired dalam 30 hari     |

### Chart

| Chart                    | Tipe        | Deskripsi                               |
| ------------------------ | ----------- | --------------------------------------- |
| Tren 12 Bulan            | Line/Bar    | Jumlah proyek baru per bulan (12 bulan) |
| Distribusi Status        | Doughnut/Pie| Proporsi status proyek                  |
| Distribusi Kategori      | Bar         | Jumlah proyek per kategori PLTS/PJUTS   |
| Top 5 Customer           | Bar         | Customer dengan proyek terbanyak        |

### Proyek Terbaru

Menampilkan 6 proyek terbaru beserta data mitra terkait.

## Status

✅ Aktif — Fitur sudah berfungsi penuh.
