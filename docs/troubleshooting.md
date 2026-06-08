# Troubleshooting

## Login gagal — "Terlalu banyak permintaan"

**Penyebab:** Rate limiting Sliding Window Throttle aktif (5 request/60 detik untuk auth).

**Solusi:**
- Tunggu 60 detik sebelum mencoba kembali.
- Cek header `Retry-After` di response 429 untuk mengetahui sisa waktu tunggu.
- Jika terjadi di development, bersihkan cache: `php artisan cache:clear`.

---

## Login gagal — 401 Unauthorized

**Kemungkinan penyebab:**
- Email atau password salah.
- JWT_SECRET belum di-generate.

**Solusi:**
- Pastikan credential benar.
- Jalankan `php artisan jwt:secret` jika belum.
- Restart server setelah mengubah `.env`.

---

## Token refresh gagal — redirect ke login terus menerus

**Kemungkinan penyebab:**
- JWT_SECRET berubah (token lama invalid).
- Token TTL sudah terlalu lama expired.

**Solusi:**
- Hapus `jwt_token` dari localStorage browser (DevTools → Application → Local Storage).
- Login ulang.
- Pastikan `JWT_SECRET` konsisten antara generate dan runtime.

---

## CORS error — "Access-Control-Allow-Origin"

**Kemungkinan penyebab:**
- Frontend mengakses backend dari origin yang berbeda.
- CORS config belum dikonfigurasi.

**Solusi:**
- Cek `backend/config/cors.php` — pastikan `allowed_origins` mencakup URL frontend.
- Untuk development, `['*']` sudah diatur.
- Restart backend server setelah mengubah config.

---

## Build error frontend — "Cannot find module"

**Kemungkinan penyebab:**
- Dependency belum diinstall.
- Versi Node.js terlalu lama.

**Solusi:**
```bash
cd frontend
rm -rf node_modules
npm install
npm run build
```

---

## Build error frontend — Svelte check error

**Solusi:**
```bash
cd frontend
npm run check
```

Perhatikan error TypeScript yang ditampilkan dan perbaiki sesuai instruksi.

---

## Data tidak muncul — API mengembalikan data kosong

**Kemungkinan penyebab:**
- Database kosong (belum di-seed).
- Filter query terlalu ketat.
- Token user tidak valid.

**Solusi:**
- Jalankan `php artisan db:seed` untuk data contoh.
- Coba request tanpa filter terlebih dahulu.
- Cek console browser untuk error 401/403.

---

## File upload gagal — 404 atau file tidak muncul

**Kemungkinan penyebab:**
- Symlink storage belum dibuat.
- Disk permission tidak memadai.
- `FILESYSTEM_DISK` tidak dikonfigurasi.

**Solusi:**
```bash
cd backend
php artisan storage:link
```

Pastikan folder `storage/app/public` writable.

---

## Migration gagal — foreign key error

**Kemungkinan penyebab:**
- Urutan migration tidak benar (tabel referensi belum ada).
- Data existing melanggar constraint.

**Solusi:**
- Untuk fresh install: `php artisan migrate:fresh --seed`
- Untuk production: periksa data yang melanggar constraint sebelum menjalankan migration baru.

---

## Tidak bisa akses dari HP / perangkat lain di jaringan lokal

**Kemungkinan penyebab:**
- Backend hanya listen di `127.0.0.1` (localhost).
- Frontend hanya listen di `localhost`.
- `PUBLIC_API_BASE_URL` masih mengarah ke `localhost`.

**Solusi:**
1. Edit `backend/.env`:
   ```
   SERVER_HOST=0.0.0.0
   # atau IP spesifik: SERVER_HOST=192.168.1.x
   ```
2. Edit `frontend/.env`:
   ```
   DEV_HOST=0.0.0.0
   HMR_HOST=192.168.1.x
   PUBLIC_API_BASE_URL=http://192.168.1.x:8000/api
   PUBLIC_STORAGE_BASE_URL=http://192.168.1.x:8000/storage
   ```
3. Restart kedua server.
4. Akses dari HP menggunakan `http://192.168.1.x:5174`.

---

## Error "Warehouse cannot be deleted"

**Penyebab:** Gudang masih memiliki inventori atau riwayat mutasi stok.

**Solusi:**
- Pindahkan/keluarkan semua stok dari gudang terlebih dahulu.
- Gudang dengan riwayat mutasi tidak bisa dihapus untuk menjaga integritas data.

---

## Error "Stock movements are immutable"

**Penyebab:** Attempt untuk mengubah atau menghapus record `stock_movements`.

**Solusi:**
- Record mutasi stok memang sengaja dibuat immutable (tidak bisa diubah/dihapus).
- Untuk koreksi stok, buat mutasi baru (inbound/outbound) sebagai penyesuaian.

---

## Permission denied — 403 Forbidden

**Kemungkinan penyebab:**
- User tidak memiliki permission yang diperlukan.
- Role user belum dikonfigurasi.

**Solusi:**
- Minta admin/super_admin untuk menambahkan permission yang diperlukan melalui Settings → Manajemen User.
- Cek endpoint `GET /api/auth/me` untuk melihat permission aktual user.
