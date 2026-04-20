# 📏 Code Standards & Changelog

> Standar penulisan kode, panduan komentar, dan format pencatatan perubahan.

---

## 💬 Inline Comments: "Mengapa" (Why), Bukan "Apa" (What)

### Filosofi

Komentar yang baik **tidak menjelaskan apa yang kode lakukan** (karena kode seharusnya sudah cukup jelas), melainkan menjelaskan **mengapa** kode tersebut ditulis dengan cara tertentu — alasan bisnis, keputusan arsitektural, atau _trade-off_ yang dipilih.

### ❌ Contoh Buruk (Menjelaskan "Apa")

```php
// Ambil user berdasarkan ID
$user = User::find($id);

// Cek apakah status proyek adalah Ongoing
if ($project->status === 'Ongoing') {
    // ...
}

// Looping setiap item
foreach ($items as $item) {
    // Simpan ke database
    $item->save();
}
```

> Komentar di atas **tidak menambah nilai** karena hanya mengulangi apa yang sudah jelas dari kode.

### ✅ Contoh Baik (Menjelaskan "Mengapa")

Berikut 6 contoh dari codebase aktual yang menunjukkan komentar dengan fokus "Why":

---

#### Contoh 1: Backend — Logika Transisi Attachment (Activity Model)

```php
/**
 * Logika Transisi: Sistem ini mendukung dua cara penyimpanan attachment:
 * 1. Cara Baru: Relasi HasMany ke tabel 'activity_attachments'.
 * 2. Cara Lama: Path file tunggal yang disimpan di kolom 'attachment'.
 *
 * Fallback ke cara lama diperlukan karena data historis masih menggunakan
 * skema kolom tunggal dan belum semua record dimigrasikan.
 */
public function getAttachmentsAttribute(): array
{
    $atts = $this->getRelationValue('attachments') ?? $this->attachments()->get();
    if ($atts && $atts->count() > 0) {
        return $atts->map(fn($att) => [...])->all();
    }

    // Jika tidak ada data di tabel relasi, cek apakah ada file di kolom legacy 'attachment'
    if (!$this->attachment) return [];
    // ...
}
```

> **Mengapa komentar ini bagus:** Menjelaskan konteks migrasi arsitektur — ada _dua sistem_ penyimpanan yang hidup berdampingan dan alasan kenapa fallback diperlukan.

---

#### Contoh 2: Backend — Sorting dengan Tie-Breaker (Activity Model)

```php
// Sorting khusus: Jika sorting berdasarkan tanggal, tambahkan ID sebagai
// tie-breaker agar pagination stabil (mencegah duplikasi/skip item
// saat dua record memiliki tanggal yang sama)
if ($sortBy === 'activity_date') {
    $query->orderBy('activity_date', $sortDir)
          ->orderBy('id', $sortDir);
} else {
    $query->orderBy('id', $sortDir);
}
```

> **Mengapa komentar ini bagus:** Menjelaskan masalah teknis (pagination instabil) yang tidak terlihat dari kode saja — tanpa komentar ini, developer lain mungkin menghapus `orderBy('id')` karena dianggap redundan.

---

#### Contoh 3: Backend — Filesystem Fallback (Activity Model)

```php
// Fallback jika database mencatat file ada tapi Storage API gagal (cek path fisik).
// Ini terjadi di lingkungan tertentu di mana symlink storage belum di-setup
// atau permission filesystem tidak konsisten.
if ($size === null) {
    $abs = storage_path('app/public/' . $rel);
    if (is_file($abs)) {
        $size = @filesize($abs) ?: null;
    }
}
```

> **Mengapa komentar ini bagus:** Menjelaskan edge case spesifik yang memotivasi fallback — tanpa komentar ini, kode terlihat seperti over-engineering yang tidak perlu.

---

#### Contoh 4: Frontend — Silent Token Refresh Queue (axiosClient.ts v1)

```typescript
/**
 * Setelah token baru didapat, resolve semua request yang sedang antri.
 *
 * Mekanisme queue ini mencegah race condition: ketika beberapa request
 * gagal 401 secara bersamaan, hanya SATU proses refresh yang berjalan,
 * sementara request lainnya menunggu di antrian dan otomatis di-retry
 * dengan token baru.
 */
const processQueue = (error: unknown, token: string | null = null) => {
  failedQueue.forEach((prom) => {
    if (token) prom.resolve(token);
    else prom.reject(error);
  });
  failedQueue = [];
};
```

> **Mengapa komentar ini bagus:** Menjelaskan _concurrency problem_ yang diselesaikan — tanpa komentar, developer lain tidak memahami mengapa ada queue dan mungkin menyederhanakan kode secara salah.

---

#### Contoh 5: Frontend — Guard untuk Auth Routes (api.ts v2)

```typescript
// HANYA handle 401 kalau request ini memang pakai auth: true.
// Request tanpa auth (seperti /auth/login) tidak boleh memicu
// redirect ke login, karena akan menyebabkan infinite loop.
if (auth && isUnauthorized(response.status, payload)) {
  // ...
}
```

> **Mengapa komentar ini bagus:** Menjelaskan bug yang akan terjadi jika guard ini dihapus (infinite redirect loop) — ini adalah _defensive coding_ yang perlu didokumentasikan alasannya.

---

#### Contoh 6: Backend — Nama Tabel Custom (Mitra Model)

```php
// Model 'Mitra' menggunakan tabel 'partners' karena tabel ini dibuat saat
// awal development dengan nama generik. Mengubah nama tabel sekarang terlalu
// berisiko karena sudah ada foreign key dari 4 tabel lain dan data produksi.
#[Table('partners')]
class Mitra extends Model
```

> **Mengapa komentar ini bagus:** Menjelaskan "hutang teknis" yang disengaja dan alasan mengapa tidak diperbaiki — mencegah developer lain menghabiskan waktu mencoba "memperbaiki" inkonsistensi ini.

---

### 📐 Aturan Komentar

| ✅ Tulis Komentar Ketika                  | ❌ Jangan Tulis Komentar Ketika                                  |
| ----------------------------------------- | ---------------------------------------------------------------- |
| Ada keputusan arsitektur yang tidak jelas | Kode sudah self-explanatory                                      |
| Ada workaround untuk bug/limitasi         | Hanya mengulangi nama fungsi/variabel                            |
| Ada edge case yang tidak intuitif         | Komentar basi/outdated (lebih buruk dari tanpa komentar)         |
| Ada alasan bisnis di balik logika         | Setiap baris kode — terlalu banyak komentar = noise              |
| Ada TODO / FIXME yang perlu di-track      | Komentar yang bisa digantikan dengan nama fungsi yang lebih baik |

### Format Komentar

```php
// ── Section Header ──────────────────────────────────────
// Gunakan format ini untuk memisahkan bagian logika besar

// Komentar single-line untuk penjelasan singkat

/**
 * Komentar multi-line untuk penjelasan yang lebih panjang
 * atau dokumentasi method/class.
 */
```

---

## 🧹 Konvensi Coding

### Backend (Laravel / PHP)

| Aspek            | Konvensi                            | Contoh                                  |
| ---------------- | ----------------------------------- | --------------------------------------- |
| Style Guide      | PSR-12 (enforced oleh Laravel Pint) | `php ./vendor/bin/pint`                 |
| Arsitektur       | Service Pattern (Thin Controller)   | `ActivityService`, `ProjectService`     |
| Naming: Model    | Singular PascalCase                 | `Project`, `BarangCertificate`          |
| Naming: Table    | Plural snake_case                   | `projects`, `barang_certificates`       |
| Naming: Method   | camelCase                           | `getFormDependencies()`                 |
| Naming: Variable | camelCase                           | `$perPage`, `$sortDir`                  |
| Query Filtering  | Eloquent Scope (`scopeFilter`)      | `Project::filter($filters)->paginate()` |
| Validasi         | Form Request classes                | `ActivityRequest`                       |
| Response         | API Resource classes                | `ActivityResource`                      |
| Permission       | Spatie middleware                   | `$this->middleware('permission:...')`   |

### Frontend (SvelteKit / TypeScript)

| Aspek          | Konvensi                                | Contoh                            |
| -------------- | --------------------------------------- | --------------------------------- |
| Style Guide    | Prettier + ESLint                       | `npm run lint && npm run format`  |
| Bahasa         | TypeScript strict                       | Selalu pakai type annotations     |
| Komponen       | Svelte 5 (runes syntax jika applicable) | `$state`, `$derived`              |
| Styling        | Tailwind CSS v4 utilities               | `class="flex items-center"`       |
| Penamaan File  | `+page.svelte`, `Modal.svelte`          | SvelteKit convention              |
| State          | Svelte writable stores                  | `writable<User \| null>(null)`    |
| API Calls      | Centralized (axiosClient / apiFetch)    | Jangan panggil `fetch()` langsung |
| Error Handling | try/catch + SweetAlert2                 | User-friendly error messages      |

---

## 📓 Template CHANGELOG.md

> Format: [Keep a Changelog](https://keepachangelog.com/id-ID/1.0.0/) + [Semantic Versioning](https://semver.org/lang/id/)

---

```markdown
# Changelog

Semua perubahan penting pada proyek ini akan didokumentasikan di file ini.

Format berdasarkan [Keep a Changelog](https://keepachangelog.com/id-ID/1.0.0/),
dan proyek ini mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [Unreleased]

### Added

- [Fitur baru yang ditambahkan]

### Changed

- [Perubahan pada fitur yang sudah ada]

### Fixed

- [Perbaikan bug]

---

## [1.3.0] - 2026-04-20

### Changed

- **Major Upgrade: Laravel 11 → 13** — Framework backend di-upgrade secara
  inkremental (11 → 12 → 13) ke Laravel 13.5.0 untuk memanfaatkan fitur
  terbaru dan dukungan keamanan jangka panjang (security fixes hingga Q1 2028).
- PHP minimum requirement dinaikkan dari ^8.2 menjadi ^8.3.
- Spatie Laravel Permission di-upgrade dari v6 ke v7.3.
- JWT Auth di-upgrade dari v2.3 ke v2.9.
- Laravel Tinker di-upgrade dari v2 ke v3.
- Seluruh model Eloquent (8 file) di-refaktor menggunakan **PHP Attributes**
  (`#[Fillable]`, `#[Hidden]`, `#[Appends]`, `#[Table]`) sebagai pengganti
  deklarasi properti klasik, mengikuti standar Laravel 13.

### Added

- Service class `AIAgentService.php` — skeleton untuk integrasi AI lanjutan
  menggunakan Laravel AI SDK (saat ini menggunakan raw HTTP calls, akan
  di-refaktor ke SDK resmi setelah upgrade PHP ke 8.4+).

### Removed

- `app/Http/Kernel.php` — File deprecated sejak Laravel 11. Middleware
  sudah dikelola sepenuhnya di `bootstrap/app.php`.
- `app/Providers/RouteServiceProvider.php` — Routing sudah dikonfigurasi
  di `bootstrap/app.php` via `withRouting()`.

---

## [1.2.0] - 2026-04-06

### Added

- Fitur AI Document Extraction pada modul Activity — ekstraksi otomatis
  data dari dokumen yang di-upload (invoice, PO, dll) menggunakan AI.
- Activity Log (audit trail) untuk semua modul — mencatat setiap
  perubahan data oleh user dengan detail sebelum/sesudah.
- Modul Finance Report — ringkasan keuangan proyek (view & update only).
- Role-Based Access Control (RBAC) dinamis — konfigurasi role dan
  permission melalui UI admin tanpa hardcode di frontend.

### Changed

- Refaktor seluruh Controller menggunakan Service Pattern (Thin Controller)
  untuk memisahkan business logic dari layer HTTP.
- Migrasi form dependencies — data dropdown form (projects, mitras) kini
  dikirim bersama response list/detail, mengurangi jumlah request API.
- Frontend v2 migrasi dari Axios ke native Fetch API (`apiFetch` wrapper)
  untuk mengurangi ukuran bundle dan dependency.

### Fixed

- Scroll trapping pada halaman Projects, Activities, dan Certificates —
  dihapus nested scroll container yang memblokir scroll native.
- Sidebar menu tidak ter-render setelah login pertama kali di v1 —
  diperbaiki inisialisasi state menu saat autentikasi berhasil.
- Silent token refresh memicu error di halaman login —
  ditambahkan guard untuk mengecualikan auth routes dari refresh logic.

### Security

- Endpoint Activity Logs dibatasi berdasarkan role — user biasa hanya bisa
  melihat log miliknya sendiri, admin/super_admin melihat seluruh log.
- Validasi role assignment diperketat — mencegah user non-admin
  mengakses endpoint `/auth/role/*`.

---

## [1.1.0] - 2026-03-01

### Added

- Multi-attachment support pada Activity dan Certificate — menggantikan
  sistem single-file pada kolom `attachment`.
- Modul Barang Certificate (CRUD) — manajemen data barang bersertifikat.
- Modul Certificate (CRUD) — dengan filter status, proyek, dan tanggal.
- Komponen `FileAttachment.svelte` — drag & drop file upload dengan
  preview, rename, dan deskripsi per-file.

### Changed

- Tabel `activities` ditambah kolom `from` dan `to` untuk mencatat
  pengirim dan penerima dokumen.
- Model Mitra ditambah dukungan multi-role: `is_customer`, `is_vendor`,
  `is_pribadi`, `is_perusahaan`.

### Deprecated

- Kolom `attachment` (string) pada tabel `activities` dan `certificates`
  — digantikan oleh tabel `activity_attachments` dan
  `certificate_attachments`. Kolom lama masih di-support sebagai fallback.

---

## [1.0.0] - 2025-06-13

### Added

- Initial release — Project Management System.
- Modul Auth: Registrasi, Login, Logout, Refresh Token (JWT).
- Modul Project: CRUD proyek dengan filter dan sorting.
- Modul Activity: CRUD aktivitas per-proyek.
- Modul Mitra: CRUD data mitra/partner.
- Modul Dashboard: Ringkasan statistik proyek.
- Frontend v1 (SvelteKit + Axios + Tailwind CSS).
- Frontend v2 (SvelteKit + Fetch API + Tailwind CSS).
```

---

### Panduan Mengisi Changelog

#### Kategori yang Tersedia

| Kategori     | Kapan Digunakan                                            |
| ------------ | ---------------------------------------------------------- |
| `Added`      | Fitur **baru** yang sebelumnya tidak ada                   |
| `Changed`    | Perubahan pada fitur yang **sudah ada**                    |
| `Deprecated` | Fitur yang **akan dihapus** di versi mendatang             |
| `Removed`    | Fitur yang **sudah dihapus**                               |
| `Fixed`      | **Perbaikan bug**                                          |
| `Security`   | Perubahan terkait **keamanan** (vulnerability, permission) |

#### Best Practices

1. **Tulis dari sudut pandang pengguna** — "Fitur X ditambahkan" lebih baik dari "Commit abc123 merged".
2. **Sertakan konteks singkat** — Jelaskan _mengapa_ perubahan dilakukan, bukan hanya _apa_.
3. **Kelompokkan perubahan terkait** — Jika 5 commit memperbaiki 1 fitur, tulis sebagai 1 entri changelog.
4. **Tanggal menggunakan format ISO** — `YYYY-MM-DD` (contoh: `2026-04-06`).
5. **Update `[Unreleased]` setiap kali merge** — Pindahkan ke versi baru saat release.
6. **Link ke issue/PR jika ada** — `Fixed login redirect loop (#42)`.

#### Contoh Entri yang Baik vs Buruk

```markdown
# ❌ Buruk

### Fixed

- Fix bug

# ✅ Baik

### Fixed

- Sidebar menu tidak ter-render setelah login pertama kali di v1 —
  state menu tidak terinisialisasi karena data permission belum tersedia
  saat komponen layout pertama kali di-mount.
```

---

## 🔧 Tools: Formatting & Linting

### Menjalankan Code Formatter

```bash
# Backend — Laravel Pint (PSR-12)
cd backend
./vendor/bin/pint

# Frontend — Prettier
cd frontend-v1      # atau frontend-v2
npm run format

# Frontend — ESLint check
npm run lint
```

### Konfigurasi Prettier (`prettierrc`)

```json
{
  "useTabs": true,
  "singleQuote": true,
  "trailingComma": "none",
  "printWidth": 100,
  "plugins": ["prettier-plugin-svelte", "prettier-plugin-tailwindcss"],
  "overrides": [{ "files": "*.svelte", "options": { "parser": "svelte" } }]
}
```
