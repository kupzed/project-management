# Feature: Aktivitas

## Deskripsi

Fitur ini digunakan untuk mencatat aktivitas keuangan dan dokumen yang terkait dengan proyek. Setiap aktivitas memiliki kategori dokumen (invoice, PO, payment, dll), nilai transaksi, dan mendukung multiple file attachment.

## Route

- Frontend: `/activities`
- API: `GET|POST /api/activities`, `GET|PUT|DELETE /api/activities/{id}`

## Komponen Utama

- `src/routes/activities/+page.svelte` — Halaman list
- `src/routes/activities/_components/` — Form, detail, filter
- `src/lib/components/FileAttachment.svelte` — Upload file
- `src/lib/components/FileAttachmentItem.svelte` — Item file

## Data Source

- Service: `src/lib/services/activityService.ts`
- Controller: `app/Http/Controllers/ActivityController.php`
- Service: `app/Services/ActivityService.php`

## Database Terkait

- `activities` — Data utama aktivitas
- `activity_attachments` — File lampiran (sistem baru)
- `projects` — Relasi proyek induk
- `partners` — Relasi mitra

## Business Rules

1. Nama aktivitas wajib diisi.
2. Proyek (`project_id`) wajib dipilih — aktivitas selalu terhubung ke proyek.
3. Jenis aktivitas: `Internal`, `Customer`, `Vendor`.
4. Kategori dokumen wajib dipilih (17 opsi: Expense Report, Invoice, Invoice & FP, Purchase Order, Payment, Quotation, Faktur Pajak, Kasbon, Laporan Teknis, Surat Masuk, Surat Keluar, Kontrak, Berita Acara, Receive Item, Delivery Order, Legalitas, Other).
5. Nilai transaksi (`value`) dalam Rupiah, default 0.
6. Tanggal aktivitas (`activity_date`) wajib diisi.
7. Mitra opsional — bisa berbeda dari mitra proyek.
8. Field `from` dan `to` opsional (pengirim dan penerima).
9. Menghapus proyek akan **cascade delete** semua aktivitas terkait.

## Sistem Attachment

Aplikasi mendukung dua cara penyimpanan attachment:

### Cara Baru (activity_attachments)
- Multiple file per aktivitas.
- Setiap file memiliki nama, deskripsi, path, MIME type, dan size.
- File disimpan di Laravel Storage disk `public`.
- Menghapus aktivitas akan cascade delete attachments.

### Cara Lama (legacy)
- Single file path di kolom `attachment` pada tabel `activities`.
- Accessor `getAttachmentsAttribute` menangani fallback ke cara lama.
- Jika ada data di tabel `activity_attachments`, data legacy diabaikan.

## Filter & Sorting

| Filter        | Deskripsi                                          |
| ------------- | -------------------------------------------------- |
| `project_id`  | Filter berdasarkan proyek                          |
| `jenis`       | Filter berdasarkan jenis (Internal/Customer/Vendor)|
| `kategori`    | Filter berdasarkan kategori dokumen                |
| `mitra_id`    | Filter berdasarkan mitra                           |
| `date_from`   | Tanggal aktivitas dari                             |
| `date_to`     | Tanggal aktivitas sampai                           |
| `search`      | Pencarian di nama, deskripsi, nama proyek, nama mitra |
| `sort_by`     | `created` (default) atau `activity_date`           |
| `sort_dir`    | `asc` atau `desc` (default: `desc`)                |

## Status

✅ Aktif — CRUD aktivitas dan attachment sudah berfungsi penuh.
