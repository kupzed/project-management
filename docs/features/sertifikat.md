# Feature: Sertifikat

## Deskripsi

Fitur ini mencakup dua modul yang saling terkait:

1. **Barang Sertifikat** — Master data barang/peralatan yang memiliki sertifikat.
2. **Sertifikat** — Sertifikat aktual yang terhubung ke proyek dan barang sertifikat, dengan tracking masa berlaku.

## Route

### Barang Sertifikat
- Frontend: `/barang-certificates`
- API: `GET|POST /api/barang-certificates`, `GET|PUT|DELETE /api/barang-certificates/{id}`

### Sertifikat
- Frontend: `/certificates`
- API: `GET|POST /api/certificates`, `GET|PUT|DELETE /api/certificates/{id}`

## Komponen Utama

- `src/routes/barang-certificates/` — Halaman barang sertifikat
- `src/routes/certificates/` — Halaman sertifikat

## Data Source

- Service: `src/lib/services/barangCertificateService.ts`
- Service: `src/lib/services/certificateService.ts`
- Controller: `app/Http/Controllers/BarangCertificateController.php`
- Controller: `app/Http/Controllers/CertificateController.php`

## Database Terkait

- `barang_certificates` — Master barang sertifikat
- `certificates` — Data sertifikat
- `certificate_attachments` — File lampiran sertifikat
- `projects` — Relasi proyek (hanya proyek dengan `is_cert_projects = true`)
- `partners` — Relasi mitra pemilik barang

## Business Rules — Barang Sertifikat

1. Nama barang wajib diisi.
2. Nomor seri (`no_seri`) wajib diisi, max 30 karakter.
3. Mitra opsional — menunjukkan pemilik/vendor barang.

## Business Rules — Sertifikat

1. Nama sertifikat wajib diisi.
2. Nomor sertifikat (`no_certificate`) wajib diisi, max 30 karakter.
3. Status sertifikat: `Belum`, `Tidak Aktif`, `Aktif`.
4. Tanggal terbit (`date_of_issue`) dan kadaluarsa (`date_of_expired`) opsional.
5. Relasi ke proyek opsional — menggunakan proyek yang `is_cert_projects = true`.
6. Relasi ke barang sertifikat opsional.
7. Mendukung file attachment (sama seperti aktivitas — dual system baru & legacy).
8. Dashboard menampilkan sertifikat yang akan expired dalam 30 hari.

## Filter — Sertifikat

| Filter                  | Deskripsi                              |
| ----------------------- | -------------------------------------- |
| `status`                | Filter berdasarkan status              |
| `project_id`            | Filter berdasarkan proyek              |
| `barang_certificate_id` | Filter berdasarkan barang sertifikat   |
| `date_from`             | Tanggal terbit dari                    |
| `date_to`               | Tanggal terbit sampai                  |
| `search`                | Pencarian di nama, nomor sertifikat, nama proyek, nama barang |

## Status

✅ Aktif — CRUD barang sertifikat dan sertifikat sudah berfungsi penuh.
