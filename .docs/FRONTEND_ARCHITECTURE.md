# 🎨 Frontend Architecture Documentation (SvelteKit)

> Panduan arsitektur, pola, dan konvensi yang digunakan di frontend SvelteKit.

---

## 📁 Hierarki Folder

Aplikasi frontend mengikuti struktur standar SvelteKit dengan beberapa konvensi tambahan.

### Struktur Umum

```
src/
├── app.html              # Template HTML utama (entry point)
├── app.css               # Global stylesheet (Tailwind CSS + custom styles)
├── app.d.ts              # Deklarasi tipe global TypeScript
│
├── lib/                  # Kode yang bisa di-import via alias $lib
│   ├── api.ts            # (v2) Fetch wrapper + auth helper
│   ├── axiosClient.ts    # (v1) Axios instance + interceptor
│   ├── config.ts         # Konfigurasi (API base URL, dll)
│   │
│   ├── components/       # Reusable UI components
│   │   ├── layout/       #   → Sidebar, Navbar, AppShell
│   │   ├── form/         #   → Input, Select, FormGroup
│   │   ├── detail/       #   → Detail view components
│   │   ├── filters/      #   → (v2) Filter components
│   │   ├── Modal.svelte
│   │   ├── Drawer.svelte
│   │   ├── Pagination.svelte
│   │   └── FileAttachment.svelte
│   │
│   ├── stores/           # Svelte stores (state management)
│   │   ├── user.ts       #   → State user yang sedang login
│   │   ├── theme.ts      #   → State tema (dark/light mode)
│   │   └── permissions.ts #  → State permission user saat ini
│   │
│   └── utils/            # Helper functions
│
├── routes/               # File-based routing (SvelteKit convention)
│   ├── +layout.svelte    # Root layout (sidebar, navbar, auth guard)
│   ├── +layout.ts        # Root layout data loader
│   ├── +page.ts          # Redirect root ke /dashboard
│   │
│   ├── auth/             # Halaman autentikasi (tanpa sidebar)
│   │   └── login/
│   │
│   ├── dashboard/        # Halaman dashboard
│   ├── projects/         # Modul proyek (list + detail)
│   ├── activities/       # Modul aktivitas
│   ├── mitras/           # Modul mitra/partner
│   ├── certificates/     # Modul sertifikat
│   ├── barang-certificates/ # Modul barang sertifikat
│   ├── finance/          # Modul keuangan
│   └── settings/         # Pengaturan (profil, role, password)
│
└── static/               # File statis (favicon, gambar, dll)
```

### Perbedaan antara v1 dan v2

| Aspek               | v1 (`frontend-v1`)                | v2 (`frontend-v2`)                          |
| ------------------- | --------------------------------- | ------------------------------------------- |
| HTTP Client         | **Axios** (`axiosClient.ts`)      | **Fetch API** native (`api.ts`)             |
| Route Grouping      | Flat routes                       | Grouped: `(app)/` dan `(auth)/`             |
| Dependency Tambahan | `axios`, `@tailwindplus/elements` | Tanpa Axios (lebih ringan)                  |
| Filter Components   | Inline di halaman                 | Terpisah di `lib/components/filters/`       |
| Confirm Dialog      | SweetAlert2 saja                  | Custom `ConfirmDialog.svelte` + SweetAlert2 |
| Svelte Version      | ^5.25                             | ^5.0                                        |

---

## 🗂️ Penjelasan File & Folder Kunci

### `src/routes/` — File-Based Routing

SvelteKit menggunakan konvensi _file-based routing_:

```
routes/
├── +page.svelte        → Komponen halaman (UI)
├── +page.ts            → Data loader (fetch data sebelum render)
├── +layout.svelte      → Layout wrapper (shared UI)
└── +layout.ts          → Layout data loader
```

**Contoh:** `routes/projects/+page.svelte` → URL: `/projects`

> [!NOTE]
> **v2** menggunakan _route groups_ dengan tanda kurung `(app)` dan `(auth)` untuk memisahkan layout bersidebar (app) dan tanpa sidebar (auth), tanpa memengaruhi URL.

```
routes/
├── (auth)/           # Layout tanpa sidebar
│   └── auth/login/
│
└── (app)/            # Layout dengan sidebar
    ├── dashboard/
    ├── projects/
    └── ...
```

### `src/lib/components/` — Reusable Components

Komponen dibagi berdasarkan fungsi:

| Folder/File             | Fungsi                                                   |
| ----------------------- | -------------------------------------------------------- |
| `layout/`               | Komponen tata letak: Sidebar, Navbar, AppShell           |
| `form/`                 | Komponen form: Input, Select, Textarea, DatePicker       |
| `detail/`               | Komponen detail view: DetailSection, InfoCard            |
| `filters/`              | (v2) Komponen filter: SearchBar, DateRange, SelectFilter |
| `Modal.svelte`          | Dialog modal generic                                     |
| `Drawer.svelte`         | Side drawer/panel                                        |
| `Pagination.svelte`     | Navigasi halaman (pagination + per_page selector)        |
| `FileAttachment.svelte` | Upload & preview file attachment                         |

### `src/lib/stores/` — Svelte Stores

Stores menyimpan state global yang reaktif.

| Store         | File             | Keterangan                                      |
| ------------- | ---------------- | ----------------------------------------------- |
| `currentUser` | `user.ts`        | Data user yang sedang login (nama, email, role) |
| `theme`       | `theme.ts`       | Preferensi tema (dark/light) & state sidebar    |
| `permissions` | `permissions.ts` | Permission user saat ini untuk kontrol akses UI |

---

## 🔄 State Management

### Pendekatan: Svelte Writable Stores

Aplikasi ini menggunakan **Svelte stores** bawaan (`writable`, `derived`) untuk manajemen state — tanpa library eksternal seperti Redux atau Zustand.

#### 1. User Store (`user.ts`)

Menyimpan data user yang sedang terautentikasi:

```typescript
// src/lib/stores/user.ts
import { writable } from "svelte/store";

export type User = {
  id?: number;
  name: string;
  email: string;
};

export const currentUser = writable<User | null>(null);

// Helper functions
export const setUser = (u: User | null) => currentUser.set(u);
export const patchUser = (partial: Partial<User>) =>
  currentUser.update((u) => (u ? { ...u, ...partial } : u));

// Hapus data user dari store & localStorage
export const clearUser = () => {
  currentUser.set(null);
  if (typeof window !== "undefined") {
    localStorage.removeItem("jwt_token"); // v1
    // localStorage.removeItem('auth_token'); // v2
  }
};
```

**Penggunaan di komponen:**

```svelte
<script lang="ts">
    import { currentUser } from '$lib/stores/user';
</script>

{#if $currentUser}
    <p>Selamat datang, {$currentUser.name}!</p>
{:else}
    <p>Silakan login terlebih dahulu.</p>
{/if}
```

#### 2. Permissions Store (`permissions.ts`)

Mengontrol tampilan UI berdasarkan permission user:

```typescript
// src/lib/stores/permissions.ts
import { writable, derived } from "svelte/store";

export const userPermissions = writable<string[]>([]);

// Helper: cek apakah user punya permission tertentu
export const hasPermission = (perm: string): boolean => {
  let perms: string[] = [];
  userPermissions.subscribe((val) => (perms = val))();
  return perms.includes(perm);
};
```

**Penggunaan untuk kontrol akses UI:**

```svelte
<script lang="ts">
    import { userPermissions } from '$lib/stores/permissions';
</script>

{#if $userPermissions.includes('project-create')}
    <button>+ Tambah Proyek</button>
{/if}
```

#### 3. Theme Store (`theme.ts`)

Mengatur dark/light mode dan state sidebar:

```typescript
// src/lib/stores/theme.ts
import { writable } from "svelte/store";

export const darkMode = writable<boolean>(false);
export const sidebarOpen = writable<boolean>(true);
```

---

## 🌐 Standar Fetching API

### v1: Axios Client (`axiosClient.ts`)

v1 menggunakan **Axios** dengan interceptor untuk JWT management:

```typescript
import axiosClient from "$lib/axiosClient";

// GET — Ambil daftar proyek
async function fetchProjects(page: number = 1) {
  const response = await axiosClient.get("/projects", {
    params: { page, per_page: 10 },
  });
  return response.data;
}

// POST — Buat proyek baru
async function createProject(data: ProjectPayload) {
  const response = await axiosClient.post("/projects", data);
  return response.data;
}

// PUT — Update proyek
async function updateProject(id: number, data: ProjectPayload) {
  const response = await axiosClient.put(`/projects/${id}`, data);
  return response.data;
}

// DELETE — Hapus proyek
async function deleteProject(id: number) {
  const response = await axiosClient.delete(`/projects/${id}`);
  return response.data;
}
```

**Fitur Interceptor v1:**

- ✅ Otomatis menyematkan `Authorization: Bearer <token>` di setiap request
- ✅ Silent token refresh saat menerima 401
- ✅ Queue mekanisme — request lain menunggu saat refresh berjalan
- ✅ Auto-redirect ke login jika refresh gagal

### v2: Native Fetch Wrapper (`api.ts`)

v2 menggunakan **Fetch API** native yang di-wrap dalam fungsi `apiFetch`:

```typescript
import { apiFetch } from "$lib/api";

// GET — Ambil daftar proyek
async function fetchProjects(page: number = 1) {
  return apiFetch<ProjectListResponse>(`/projects?page=${page}&per_page=10`, {
    auth: true, // ← tambahkan ini untuk menyertakan Bearer token
  });
}

// POST — Buat proyek baru
async function createProject(data: ProjectPayload) {
  return apiFetch<ProjectResponse>("/projects", {
    method: "POST",
    body: data,
    auth: true,
  });
}

// POST dengan FormData (upload file)
async function createActivity(formData: FormData) {
  return apiFetch<ActivityResponse>("/activities", {
    method: "POST",
    body: formData, // FormData otomatis menghapus Content-Type header
    auth: true,
  });
}

// DELETE — Hapus proyek
async function deleteProject(id: number) {
  return apiFetch("/projects/" + id, {
    method: "DELETE",
    auth: true,
  });
}
```

**Fitur Fetch Wrapper v2:**

- ✅ Type-safe dengan generics TypeScript (`apiFetch<T>`)
- ✅ Otomatis mendeteksi `FormData` vs JSON body
- ✅ Silent token refresh pada 401/419
- ✅ Deduplicate concurrent refresh calls
- ✅ Tanpa dependency Axios (lebih ringan)

---

## 🔁 Pola-Pola Umum

### 1. Form Dependencies

Backend mengirimkan `form_dependencies` bersama response list/detail untuk menghindari request tambahan:

```typescript
// Response dari GET /api/activities
{
  "data": [...],
  "form_dependencies": {
    "projects": [{ "id": 1, "name": "PLTS Hybrid" }],
    "mitras": [{ "id": 1, "nama": "PT Energi" }]
  }
}
```

**Penggunaan di frontend:**

```svelte
<script lang="ts">
    let projects: SelectOption[] = [];
    let mitras: SelectOption[] = [];

    async function loadActivities() {
        const res = await fetchActivities();
        activities = res.data;

        // Ambil dependensi form dari response yang sama
        projects = res.form_dependencies.projects;
        mitras = res.form_dependencies.mitras;
    }
</script>

<!-- Dropdown proyek yang terisi dari form_dependencies -->
<select>
    {#each projects as project}
        <option value={project.id}>{project.name}</option>
    {/each}
</select>
```

### 2. Pagination Pattern

```svelte
<script lang="ts">
    let currentPage = 1;
    let lastPage = 1;
    let perPage = 10;

    async function loadData(page: number = 1) {
        const res = await apiFetch(`/projects?page=${page}&per_page=${perPage}`, {
            auth: true
        });
        items = res.data;
        currentPage = res.meta.current_page;
        lastPage = res.meta.last_page;
    }
</script>

<Pagination
    {currentPage}
    {lastPage}
    {perPage}
    on:pageChange={(e) => loadData(e.detail)}
    on:perPageChange={(e) => { perPage = e.detail; loadData(1); }}
/>
```

### 3. Filter & Search Pattern

```svelte
<script lang="ts">
    let search = '';
    let statusFilter = '';
    let debounceTimer: ReturnType<typeof setTimeout>;

    function handleSearch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadData(1), 300);
    }

    async function loadData(page: number = 1) {
        const params = new URLSearchParams({
            page: String(page),
            per_page: String(perPage),
            ...(search && { search }),
            ...(statusFilter && { status: statusFilter })
        });

        const res = await apiFetch(`/projects?${params}`, { auth: true });
        // ...handle response
    }
</script>

<input type="text" bind:value={search} on:input={handleSearch}
       placeholder="Cari proyek..." />

<select bind:value={statusFilter} on:change={() => loadData(1)}>
    <option value="">Semua Status</option>
    <option value="Ongoing">Ongoing</option>
    <option value="Complete">Complete</option>
</select>
```

---

## 🎨 Styling: Tailwind CSS v4

Kedua frontend menggunakan **Tailwind CSS v4** dengan plugin `@tailwindcss/vite`:

```typescript
// vite.config.ts
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  plugins: [tailwindcss(), sveltekit()],
});
```

Global styles didefinisikan di `src/app.css`:

```css
@import "tailwindcss";

/* Custom styles & overrides */
```

Plugin yang digunakan:

- `@tailwindcss/forms` — Reset & styling form elements
- `@tailwindcss/typography` — Prose styling untuk konten rich-text

---

## 📐 Konvensi Penamaan

| Item            | Konvensi                                  | Contoh                                   |
| --------------- | ----------------------------------------- | ---------------------------------------- |
| Komponen Svelte | PascalCase                                | `ProjectList.svelte`, `Modal.svelte`     |
| File TypeScript | camelCase                                 | `axiosClient.ts`, `permissions.ts`       |
| Store variable  | camelCase                                 | `currentUser`, `darkMode`                |
| Route folder    | kebab-case                                | `barang-certificates/`, `activity-logs/` |
| CSS class       | Tailwind utilities                        | `class="flex items-center gap-2"`        |
| Event handler   | `handle` prefix                           | `handleSubmit`, `handleSearch`           |
| API function    | `fetch`/`create`/`update`/`delete` prefix | `fetchProjects()`, `createActivity()`    |
