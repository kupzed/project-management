# Frontend Guidelines

## Teknologi

- **Framework:** SvelteKit 5 (Svelte 5 dengan Runes)
- **Styling:** Tailwind CSS 4 + `@tailwindcss/forms` + `@tailwindcss/typography`
- **HTTP Client:** Axios
- **Charts:** Chart.js
- **Dialogs:** SweetAlert2
- **UI Components:** @tailwindplus/elements
- **SSR:** Dimatikan (`ssr = false`) — aplikasi berjalan sepenuhnya sebagai SPA

## Aturan Bahasa UI

- Semua teks UI user-facing menggunakan **Bahasa Indonesia**.
- Istilah teknis seperti "login", "email", "password", "dashboard" boleh tetap dalam Bahasa Inggris.
- Pesan error dan validasi menggunakan Bahasa Indonesia.
- Confirm dialog menggunakan Bahasa Indonesia (contoh: "Apakah Anda yakin ingin logout?").

## Aturan Komponen

### Struktur

| Jenis          | Lokasi                            | Keterangan                        |
| -------------- | --------------------------------- | --------------------------------- |
| Page           | `src/routes/{module}/`            | File-based routing SvelteKit      |
| Layout         | `src/routes/+layout.svelte`       | Shared layout (sidebar, topnav)   |
| Reusable UI    | `src/lib/components/`             | Komponen yang dipakai > 1 halaman |
| Page-specific  | `src/routes/{module}/_components/`| Komponen khusus halaman tertentu  |

### Konvensi penamaan

- File Svelte: `PascalCase.svelte` (contoh: `Pagination.svelte`, `FileAttachment.svelte`)
- File TypeScript: `camelCase.ts` (contoh: `authService.ts`, `formatters.ts`)
- Route folder: `kebab-case` (contoh: `barang-certificates`, `stock-movements`)

## Aturan Service Layer

- Setiap modul memiliki file service di `src/lib/services/`.
- Service menggunakan `axiosClient` (bukan axios langsung).
- Service mengembalikan data yang sudah di-unwrap dari response.
- Tipe data didefinisikan di `src/lib/types/`.

Contoh pola:

```typescript
// src/lib/services/projectService.ts
import axiosClient from '$lib/axiosClient';

export async function getProjects(params) {
  const { data } = await axiosClient.get('/projects', { params });
  return data;
}
```

## Aturan Store

- Gunakan Svelte `writable` store untuk state global.
- Store disimpan di `src/lib/stores/`.
- Store saat ini:
  - `user.ts` — Data user yang sedang login
  - `permissions.ts` — Roles dan permissions user
  - `theme.ts` — Dark/light theme preference

## Aturan Form

- Setiap form harus memiliki validasi (client-side dan/atau server-side).
- Gunakan SweetAlert2 untuk feedback (success toast, error toast).
- Setiap submit button harus memiliki loading state.
- Form yang mengandung file upload menggunakan `FormData` (lihat `utils/form-data.ts`).

## Aturan Loading State

- Tampilkan loading indicator saat menunggu API response.
- Gunakan skeleton atau spinner yang sesuai konteks.
- Disable tombol submit selama proses berlangsung.

## Aturan Empty State

- Tampilkan pesan informatif ketika tidak ada data.
- Berikan guidance ke user tentang cara menambah data.

## Aturan Error State

- Tampilkan error message yang user-friendly dalam Bahasa Indonesia.
- Jangan membocorkan detail internal (stack trace, SQL error).
- Gunakan `utils/errors.ts` untuk standardisasi error handling.
- Gunakan `utils/toast.ts` untuk menampilkan notifikasi error.

## Format Data

### Mata Uang

- Format: Rupiah (Rp)
- Gunakan formatter dari `utils/formatters.ts`

### Tanggal

- Format tampilan: Bahasa Indonesia (contoh: "8 Juni 2026")
- Format input: ISO format `YYYY-MM-DD`

### Angka

- Gunakan pemisah ribuan sesuai locale Indonesia

## Tema

Aplikasi mendukung **dark mode** dan **light mode** melalui store `theme.ts`. Konfigurasi CSS menggunakan variabel CSS custom di `app.css`.

## Responsive Design

- Layout menggunakan sidebar desktop (`lg:pl-64`) + mobile sidebar (drawer).
- Konten utama bersifat responsive dengan padding yang berbeda per breakpoint.
- Gunakan class Tailwind responsive (`md:`, `lg:`) untuk menyesuaikan layout.

## Component Library

- **@tailwindplus/elements** digunakan untuk komponen UI dasar.
- Komponen custom meliputi: `Modal`, `Drawer`, `Pagination`, `FileAttachment`, `ConfirmDialog`.
- Styling konsisten menggunakan Tailwind utility classes.
