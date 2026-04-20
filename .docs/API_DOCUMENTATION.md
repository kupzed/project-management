# 📡 Backend & API Documentation

> Dokumentasi teknis untuk RESTful API yang dibangun menggunakan **Laravel 13** dengan autentikasi **JWT (JSON Web Token)**.

---

## 📌 Gambaran Umum API

### Base URL

| Environment       | Base URL                    |
| ----------------- | --------------------------- |
| Local Development | `http://localhost:8000/api` |
| Staging           | `[URL_STAGING]/api`         |
| Production        | `[URL_PRODUCTION]/api`      |

### Autentikasi

API menggunakan **JWT Bearer Token** melalui library `php-open-source-saver/jwt-auth`.

**Alur Autentikasi:**

```
1. POST /api/auth/login    → Mendapat access_token
2. Request + Bearer token  → Akses resource
3. POST /api/auth/refresh  → Perpanjang token (silent refresh)
4. POST /api/auth/logout   → Invalidasi token
```

**Header Autentikasi:**

```
Authorization: Bearer <access_token>
```

### Format Response Standar

Semua response API menggunakan format konsisten:

**Success Response:**

```json
{
  "message": "Data retrieved successfully",
  "data": { ... }
}
```

**Paginated Response (API Resource Collection):**

```json
{
  "data": [ ... ],
  "links": {
    "first": "http://localhost:8000/api/resource?page=1",
    "last": "http://localhost:8000/api/resource?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/resource?page=2"
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 42
  },
  "message": "Data retrieved successfully",
  "form_dependencies": { ... }
}
```

**Error Response:**

```json
{
  "message": "Pesan error yang deskriptif",
  "errors": {
    "field_name": ["Pesan validasi 1", "Pesan validasi 2"]
  }
}
```

---

## 📝 Template Dokumentasi Endpoint

> Berikut adalah template standar untuk mendokumentasikan setiap endpoint API. Gunakan format ini sebagai acuan.

---

### `POST /api/activities`

**Deskripsi:** Membuat aktivitas baru pada sebuah proyek. Mendukung upload multi-attachment dan fitur AI Document Extraction.

#### Authorization

| Tipe         | Value                                  | Permission        |
| ------------ | -------------------------------------- | ----------------- |
| Bearer Token | `Authorization: Bearer <access_token>` | `activity-create` |

#### Headers

```
Authorization: Bearer <access_token>
Content-Type: multipart/form-data
Accept: application/json
```

> [!NOTE]
> Gunakan `Content-Type: multipart/form-data` jika request menyertakan file upload. Untuk request JSON tanpa file, gunakan `Content-Type: application/json`.

#### Request Body / Payload

| Parameter                   | Tipe       | Wajib | Keterangan                                                  |
| --------------------------- | ---------- | ----- | ----------------------------------------------------------- |
| `name`                      | `string`   | ✅    | Nama aktivitas                                              |
| `project_id`                | `integer`  | ✅    | ID proyek terkait                                           |
| `jenis`                     | `enum`     | ✅    | Jenis: `Internal`, `Customer`, `Vendor`                     |
| `kategori`                  | `enum`     | ✅    | Kategori dokumen (lihat daftar di bawah)                    |
| `description`               | `string`   | ✅    | Deskripsi lengkap aktivitas                                 |
| `short_desc`                | `string`   | ❌    | Ringkasan singkat                                           |
| `value`                     | `decimal`  | ❌    | Nilai nominal (default: `0.00`)                             |
| `activity_date`             | `date`     | ✅    | Tanggal aktivitas (`YYYY-MM-DD`)                            |
| `mitra_id`                  | `integer`  | ❌    | ID mitra terkait (wajib jika `jenis` = `Customer`/`Vendor`) |
| `from`                      | `string`   | ❌    | Pengirim/asal dokumen                                       |
| `to`                        | `string`   | ❌    | Penerima/tujuan dokumen                                     |
| `attachments[]`             | `file[]`   | ❌    | Array file attachment                                       |
| `attachment_names[]`        | `string[]` | ❌    | Nama file (sesuai urutan `attachments[]`)                   |
| `attachment_descriptions[]` | `string[]` | ❌    | Deskripsi file (sesuai urutan `attachments[]`)              |

**Daftar Nilai `kategori`:**

```
Expense Report, Invoice, Invoice & FP, Purchase Order, Payment,
Quotation, Faktur Pajak, Kasbon, Laporan Teknis, Surat Masuk,
Surat Keluar, Kontrak, Berita Acara, Receive Item,
Delivery Order, Legalitas, Other
```

#### Contoh Request (cURL)

```bash
curl -X POST http://localhost:8000/api/activities \
  -H "Authorization: Bearer eyJ0eXAiOi..." \
  -H "Accept: application/json" \
  -F "name=Invoice Pembelian Panel Surya" \
  -F "project_id=3" \
  -F "jenis=Vendor" \
  -F "kategori=Invoice" \
  -F "description=Invoice pembelian 200 unit panel surya monocrystalline" \
  -F "value=150000000.00" \
  -F "activity_date=2026-04-06" \
  -F "mitra_id=5" \
  -F "from=PT Supplier Energi" \
  -F "to=PT Indogreen" \
  -F "attachments[]=@/path/to/invoice.pdf" \
  -F "attachment_names[]=Invoice-2026-001" \
  -F "attachment_descriptions[]=Invoice asli dari supplier"
```

#### Contoh Response

**✅ Success — `201 Created`**

```json
{
  "message": "Activity created successfully",
  "data": {
    "id": 42,
    "name": "Invoice Pembelian Panel Surya",
    "project_id": 3,
    "project": {
      "id": 3,
      "name": "PLTS Hybrid Kalimantan"
    },
    "jenis": "Vendor",
    "kategori": "Invoice",
    "description": "Invoice pembelian 200 unit panel surya monocrystalline",
    "short_desc": null,
    "value": "150000000.00",
    "activity_date": "2026-04-06",
    "mitra_id": 5,
    "mitra": {
      "id": 5,
      "nama": "PT Supplier Energi"
    },
    "from": "PT Supplier Energi",
    "to": "PT Indogreen",
    "attachments": [
      {
        "id": 1,
        "name": "Invoice-2026-001",
        "description": "Invoice asli dari supplier",
        "size": 245760,
        "sizeLabel": "240KB",
        "path": "activities/42/invoice.pdf",
        "url": "http://localhost:8000/storage/activities/42/invoice.pdf"
      }
    ],
    "created_at": "2026-04-06T09:30:00.000000Z",
    "updated_at": "2026-04-06T09:30:00.000000Z"
  }
}
```

**❌ Validation Error — `422 Unprocessable Entity`**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "project_id": ["The selected project id is invalid."],
    "jenis": ["The selected jenis is invalid."],
    "activity_date": ["The activity date must be a valid date."]
  }
}
```

**❌ Unauthorized — `401 Unauthorized`**

```json
{
  "message": "Unauthenticated."
}
```

**❌ Forbidden — `403 Forbidden`**

```json
{
  "message": "User does not have the right permissions."
}
```

**❌ Server Error — `500 Internal Server Error`**

```json
{
  "message": "Failed to extract document",
  "error": "AI service unavailable"
}
```

---

## 📋 Daftar Seluruh Endpoint API

### 🔐 Auth & User

| Method | Endpoint             | Deskripsi                 | Permission            |
| ------ | -------------------- | ------------------------- | --------------------- |
| `POST` | `/api/auth/register` | Registrasi user baru      | Public (rate limited) |
| `POST` | `/api/auth/login`    | Login & dapatkan token    | Public (rate limited) |
| `POST` | `/api/auth/logout`   | Logout & invalidasi token | Authenticated         |
| `POST` | `/api/auth/refresh`  | Refresh JWT token         | Authenticated         |
| `GET`  | `/api/auth/me`       | Profil user saat ini      | Authenticated         |
| `PUT`  | `/api/auth/profile`  | Update profil user        | Authenticated         |
| `PUT`  | `/api/auth/password` | Ganti password            | Authenticated         |

### 📊 Dashboard

| Method | Endpoint         | Deskripsi                | Permission    |
| ------ | ---------------- | ------------------------ | ------------- |
| `GET`  | `/api/dashboard` | Data ringkasan dashboard | Authenticated |

### 📁 Projects

| Method   | Endpoint             | Deskripsi                          | Permission       |
| -------- | -------------------- | ---------------------------------- | ---------------- |
| `GET`    | `/api/projects`      | Daftar proyek (paginated + filter) | `project-view`   |
| `POST`   | `/api/projects`      | Buat proyek baru                   | `project-create` |
| `GET`    | `/api/projects/{id}` | Detail proyek                      | `project-view`   |
| `PUT`    | `/api/projects/{id}` | Update proyek                      | `project-update` |
| `DELETE` | `/api/projects/{id}` | Hapus proyek                       | `project-delete` |

**Query Parameters (GET /api/projects):**

| Param              | Tipe     | Keterangan                                                         |
| ------------------ | -------- | ------------------------------------------------------------------ |
| `page`             | `int`    | Halaman pagination                                                 |
| `per_page`         | `int`    | Jumlah per halaman (10, 25, 50, 100)                               |
| `search`           | `string` | Pencarian berdasarkan nama, deskripsi, lokasi, no_po, no_so, mitra |
| `status`           | `string` | Filter: `Ongoing`, `Prospect`, `Complete`, `Cancel`                |
| `kategori`         | `string` | Filter kategori proyek                                             |
| `customer_id`      | `int`    | Filter berdasarkan mitra/customer                                  |
| `is_cert_projects` | `bool`   | Filter proyek sertifikasi                                          |
| `date_from`        | `date`   | Tanggal mulai range                                                |
| `date_to`          | `date`   | Tanggal akhir range                                                |
| `sort_by`          | `string` | Kolom sort: `created`, `start_date`                                |
| `sort_dir`         | `string` | Arah sort: `asc`, `desc`                                           |

### 🤝 Mitras (Partner)

| Method   | Endpoint           | Deskripsi                         | Permission     |
| -------- | ------------------ | --------------------------------- | -------------- |
| `GET`    | `/api/mitras`      | Daftar mitra (paginated + filter) | `mitra-view`   |
| `POST`   | `/api/mitras`      | Buat mitra baru                   | `mitra-create` |
| `GET`    | `/api/mitras/{id}` | Detail mitra                      | `mitra-view`   |
| `PUT`    | `/api/mitras/{id}` | Update mitra                      | `mitra-update` |
| `DELETE` | `/api/mitras/{id}` | Hapus mitra                       | `mitra-delete` |

### 📝 Activities

| Method   | Endpoint               | Deskripsi                             | Permission        |
| -------- | ---------------------- | ------------------------------------- | ----------------- |
| `GET`    | `/api/activities`      | Daftar aktivitas (paginated + filter) | `activity-view`   |
| `POST`   | `/api/activities`      | Buat aktivitas / AI Extract           | `activity-create` |
| `GET`    | `/api/activities/{id}` | Detail aktivitas                      | `activity-view`   |
| `PUT`    | `/api/activities/{id}` | Update aktivitas                      | `activity-update` |
| `DELETE` | `/api/activities/{id}` | Hapus aktivitas                       | `activity-delete` |

### 📦 Barang Certificates

| Method   | Endpoint                        | Deskripsi                   | Permission                  |
| -------- | ------------------------------- | --------------------------- | --------------------------- |
| `GET`    | `/api/barang-certificates`      | Daftar barang sertifikat    | `barang-certificate-view`   |
| `POST`   | `/api/barang-certificates`      | Buat barang sertifikat baru | `barang-certificate-create` |
| `GET`    | `/api/barang-certificates/{id}` | Detail barang sertifikat    | `barang-certificate-view`   |
| `PUT`    | `/api/barang-certificates/{id}` | Update barang sertifikat    | `barang-certificate-update` |
| `DELETE` | `/api/barang-certificates/{id}` | Hapus barang sertifikat     | `barang-certificate-delete` |

### 📜 Certificates

| Method   | Endpoint                 | Deskripsi                              | Permission           |
| -------- | ------------------------ | -------------------------------------- | -------------------- |
| `GET`    | `/api/certificates`      | Daftar sertifikat (paginated + filter) | `certificate-view`   |
| `POST`   | `/api/certificates`      | Buat sertifikat baru                   | `certificate-create` |
| `GET`    | `/api/certificates/{id}` | Detail sertifikat                      | `certificate-view`   |
| `PUT`    | `/api/certificates/{id}` | Update sertifikat                      | `certificate-update` |
| `DELETE` | `/api/certificates/{id}` | Hapus sertifikat                       | `certificate-delete` |

### 💰 Finance

| Method | Endpoint            | Deskripsi            | Permission       |
| ------ | ------------------- | -------------------- | ---------------- |
| `GET`  | `/api/finance`      | Laporan keuangan     | `finance-view`   |
| `PUT`  | `/api/finance/{id}` | Update data keuangan | `finance-update` |

### 📋 Activity Logs (Audit Trail)

| Method   | Endpoint                                   | Deskripsi            | Permission    |
| -------- | ------------------------------------------ | -------------------- | ------------- |
| `GET`    | `/api/activity-logs`                       | Semua log aktivitas  | Authenticated |
| `GET`    | `/api/activity-logs/recent`                | Log terbaru          | Authenticated |
| `GET`    | `/api/activity-logs/stats`                 | Statistik log        | Authenticated |
| `GET`    | `/api/activity-logs/filter-options`        | Opsi filter log      | Authenticated |
| `GET`    | `/api/activity-logs/{modelType}/{modelId}` | Log per-model        | Authenticated |
| `GET`    | `/api/activity-logs/export`                | Export log           | Authenticated |
| `DELETE` | `/api/activity-logs`                       | Hapus log milik user | Authenticated |

> [!NOTE]
> Activity Logs menerapkan kontrol akses berbasis role: user biasa hanya melihat log miliknya sendiri, admin/super_admin melihat seluruh log sistem.

### 🔑 Roles & Permissions (Admin Only)

| Method | Endpoint                | Deskripsi                | Permission              |
| ------ | ----------------------- | ------------------------ | ----------------------- |
| `GET`  | `/api/auth/role/users`  | Daftar user + role       | `super_admin` / `admin` |
| `PUT`  | `/api/auth/role`        | Update role user         | `super_admin` / `admin` |
| `GET`  | `/api/auth/role/config` | Konfigurasi role & modul | `super_admin` / `admin` |

---

## 🔒 Error Handling & Rate Limiting

### Rate Limiting

| Route Group               | Limit                                                |
| ------------------------- | ---------------------------------------------------- |
| `auth/*` (login/register) | `sliding_throttle:5,60,auth` (5 req/60s)             |
| Semua API route lainnya   | `sliding_throttle:15,60,api` (15 req/60s)            |

### HTTP Status Code yang Digunakan

| Code  | Arti                  | Kapan Digunakan                |
| ----- | --------------------- | ------------------------------ |
| `200` | OK                    | GET/PUT berhasil               |
| `201` | Created               | POST berhasil membuat resource |
| `401` | Unauthorized          | Token tidak valid / expired    |
| `403` | Forbidden             | Permission tidak mencukupi     |
| `404` | Not Found             | Resource tidak ditemukan       |
| `422` | Unprocessable Entity  | Validasi gagal                 |
| `429` | Too Many Requests     | Rate limit terlampaui          |
| `500` | Internal Server Error | Kesalahan server               |

---

## 📂 Pola Arsitektur Backend

### Service Pattern (Thin Controller)

Aplikasi ini menerapkan pola **Service Layer** untuk memisahkan business logic dari controller:

```
Request → Controller (validasi & routing)
              ↓
         Service (business logic, query, file handling)
              ↓
         Model (Eloquent ORM, relasi, scope)
              ↓
         Resource (transformasi response JSON)
```

**Daftar Service yang tersedia:**

| Service                       | Keterangan                            |
| ----------------------------- | ------------------------------------- |
| `ActivityService`             | CRUD aktivitas, attachment management |
| `AIDocumentExtractionService` | Integrasi AI untuk ekstraksi dokumen  |
| `AIAgentService`              | Skeleton AI agent untuk fitur lanjutan|
| `ActivityLogService`          | Audit trail & log management          |
| `AuthService`                 | Registrasi & autentikasi              |
| `BarangCertificateService`    | CRUD barang sertifikat                |
| `CertificateService`          | CRUD sertifikat + attachment          |
| `FinanceService`              | Laporan keuangan                      |
| `MitraService`                | CRUD mitra/partner                    |
| `ProjectService`              | CRUD proyek + form dependencies       |

---

# 📝 File-Based Activity Logging System

Sistem pencatatan aktivitas berbasis file untuk backend Laravel. Sistem ini menyimpan semua aktivitas user dalam file JSON per user, bukan di database, untuk menghemat ruang database dan performa server.

## ✨ Fitur Utama

- ✅ **File-based storage** - Aktivitas disimpan dalam file JSON per user
- ✅ **Automatic logging** - Otomatis mencatat CRUD operations
- ✅ **Role-Based Access Control** - Admin/Super Admin melihat log global, user biasa hanya melihat log sendiri
- ✅ **File rotation** - Otomatis memutar file saat ukuran melebihi batas
- ✅ **Cleanup system** - Menghapus file lama secara otomatis
- ✅ **Filtering & search** - Pencarian dan filter aktivitas (global/spesifik)
- ✅ **Export functionality** - Export log ke JSON
- ✅ **Statistics** - Statistik aktivitas real-time
- ✅ **Middleware support** - Middleware untuk mencatat aktivitas otomatis

## 📂 Struktur File

```
storage/app/activity-logs/
├── user_1/
│   ├── 2025-07-29.json
│   ├── 2025-07-28.json
│   └── 2025-07-27.json
├── user_2/
│   ├── 2025-07-29.json
│   └── 2025-07-28.json
└── ...
```

## ⚙️ Konfigurasi

### 🛠️ Service Configuration

File: `app/Services/ActivityLogService.php`

```php
protected $logPath = 'activity-logs';           // Path penyimpanan
protected $maxFileSize = 10485760;              // 10MB per file
protected $maxFilesPerUser = 100;               // Max 100 file per user
```

### 🔐 Role-Based Access Control (RBAC)

Sistem ini sekarang mendukung pembatasan akses berdasarkan role:

- **Super Admin & Admin**: Dapat melihat log dari semua user secara global.
- **User Lainnya**: Hanya dapat melihat riwayat aktivitas mereka sendiri.

## 🚀 Penggunaan

### 1. Otomatis Logging (Model)

Tambahkan trait `LogsActivity` ke model:

```php
use App\Models\Traits\LogsActivity;

class Project extends Model
{
    use HasFactory, LogsActivity;

    // Method untuk nama yang deskriptif
    public function getActivityNameAttribute()
    {
        return $this->name ?? 'Project #' . $this->id;
    }
}
```

### 2. Manual Logging

```php
use App\Services\ActivityLogService;

$service = new ActivityLogService();

// Log aktivitas
$service->log(
    'created',                    // action
    Project::class,              // model_type
    $project->id,                // model_id
    $project->name,              // model_name
    'Created new project',       // description
    null,                        // old_values
    $project->toArray()          // new_values
);

// Method untuk mengambil log dengan filter role
$service->getLogs($filters, $userId = null); // $userId null berarti global (Admin only)
$service->getRecentLogs($limit = 10, $userId = null);
$service->getStats($userId = null);
$service->getFilterOptions($userId = null);
```

### 3. Helper Functions

```php
use App\Helpers\ActivityHelper;

// Log user action
ActivityHelper::logUserAction('login', 'User logged in');

// Log model action
ActivityHelper::logModelAction('view', $project, 'Viewed project details');

// Log export
ActivityHelper::logExport(Project::class, 'Exported projects to Excel');
```

## 📡 API Endpoints

### 🔍 Get Activity Logs

> [!NOTE]
> Response tergantung pada role user. Admin akan menerima log global, sedangkan user biasa hanya log pribadi.

```http
GET /api/activity-logs
GET /api/activity-logs?action=created&model_type=App\Models\Project
GET /api/activity-logs?date_from=2025-07-01&date_to=2025-07-31
GET /api/activity-logs?search=project
GET /api/activity-logs?user_id=1  (Admin only)
```

### Get Recent Activities

```http
GET /api/activity-logs/recent
```

### Get Statistics

```http
GET /api/activity-logs/stats
```

### Get Filter Options

```http
GET /api/activity-logs/filter-options
```

### Get Model Logs

> [!TIP]
> Admin dapat melihat seluruh riwayat perubahan pada model ini oleh siapapun. User biasa hanya melihat perubahannya sendiri.

```http
GET /api/activity-logs/{modelType}/{modelId}
Contoh: GET /api/activity-logs/App.Models.Project/123
```

### Export Logs

```http
GET /api/activity-logs/export?action=created&date_from=2025-07-01
```

### Delete User Logs

```http
DELETE /api/activity-logs
```

## 🔧 Maintenance Commands

### Clean Old Logs

```bash
# Clean logs older than 30 days (default)
php artisan activity-logs:clean

# Clean logs older than 60 days
php artisan activity-logs:clean --days=60

# Clean logs for specific user
php artisan activity-logs:clean --user=1 --days=30
```

### Schedule Cleanup (Optional)

Tambahkan ke `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('activity-logs:clean --days=30')->weekly();
```

## 🛡️ Middleware

Aktifkan middleware untuk mencatat aktivitas otomatis:

```php
// bootstrap/app.php — withMiddleware()
$middleware->append(\App\Http\Middleware\LogUserActivity::class);
```

## 📦 Response Format

### Activity Log Response

```json
{
  "success": true,
  "data": [
    {
      "id": "log_64f8a1b2c3d4e5f6",
      "user_id": 1,
      "user_name": "John Doe",
      "action": "created",
      "model_type": "App\\Models\\Project",
      "model_id": 123,
      "model_name": "Project ABC",
      "description": "Created new Project",
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "timestamp": "2025-07-29T10:30:00.000000Z",
      "created_at": "2025-07-29T10:30:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

### Statistics Response

```json
{
  "success": true,
  "data": {
    "total_activities": 1250,
    "today_activities": 45,
    "this_week_activities": 234,
    "this_month_activities": 890,
    "actions_count": {
      "created": 450,
      "updated": 600,
      "deleted": 50,
      "viewed": 150
    },
    "models_count": {
      "App\\Models\\Project": 300,
      "App\\Models\\Activity": 500,
      "App\\Models\\Mitra": 450
    }
  }
}
```

## 💎 Keuntungan File-Based System

1. **Database Performance** - Tidak membebani database
2. **Scalability** - Mudah di-scale dengan storage terpisah
3. **Backup** - Mudah di-backup dan restore
4. **Archive** - Bisa di-archive ke storage lama
5. **Cost Effective** - Lebih murah dari database storage
6. **Flexibility** - Mudah dimodifikasi format dan struktur

## 🔍 Monitoring & Maintenance

### File Size Monitoring

- Setiap file maksimal 10MB
- Otomatis rotate saat melebihi batas
- Maksimal 100 file per user

### Cleanup Strategy

- Hapus file lama secara berkala
- Gunakan command `activity-logs:clean`
- Schedule cleanup otomatis

### Storage Monitoring

```bash
# Check storage usage
du -sh storage/app/activity-logs/

# Check file count per user
find storage/app/activity-logs/ -type f | wc -l
```

## 🛠️ Troubleshooting

### 🔑 File Permission Issues

```bash
# Set proper permissions
chmod -R 755 storage/app/activity-logs/
chown -R www-data:www-data storage/app/activity-logs/
```

### 💾 Storage Full

```bash
# Clean old logs immediately
php artisan activity-logs:clean --days=7

# Check disk usage
df -h
```

### ⚡ Performance Issues

- Kurangi `maxFileSize` jika file terlalu besar
- Kurangi `maxFilesPerUser` jika terlalu banyak file
- Implementasi caching untuk query yang sering

## 🔒 Security Considerations

1. **Access Control** - Pastikan endpoint log dilindungi dengan role yang tepat
2. **File Permissions** - Pastikan file log tidak bisa diakses publik secara langsung
3. **Data Privacy** - Hapus data sensitif (seperti password) sebelum logging
4. **Audit Integrity** - Jangan izinkan penghapusan log global kecuali oleh Super Admin
5. **Role Leakage** - Pastikan user biasa tidak bisa melihat log user lain melalui manipulasi parameter `user_id`

## 🔄 Migration dari Database

Jika sebelumnya menggunakan database:

1. Export data dari tabel `activity_logs`
2. Convert ke format file JSON
3. Place di struktur folder yang sesuai
4. Update kode untuk menggunakan service baru
5. Drop tabel lama setelah testing

## Support

Untuk pertanyaan atau masalah:

1. Check log Laravel di `storage/logs/laravel.log`
2. Verify file permissions
3. Check storage disk space
4. Review service configuration
