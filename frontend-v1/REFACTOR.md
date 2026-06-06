# Frontend Refactor Guide

Dokumen ini menjelaskan struktur dan konvensi hasil refactor `frontend-v1`.
Gunakan pola ini saat menambah atau mengubah kode agar implementasi tetap konsisten.

## Stack

- SvelteKit 2
- Svelte 5 runes
- TypeScript strict
- Tailwind CSS 4
- Axios melalui `src/lib/axiosClient.ts`
- SweetAlert2 untuk dialog konfirmasi dan toast

## Struktur Folder

```text
src/
├── lib/
│   ├── components/
│   │   ├── common/       Komponen bisnis yang digunakan lintas halaman
│   │   ├── detail/       Tampilan detail entity
│   │   ├── form/         Form dan modal entity
│   │   ├── layout/       Shell, sidebar, dan top navigation
│   │   └── ui/           Primitive UI reusable
│   ├── composables/      State atau behavior reusable
│   ├── constants/        Konfigurasi dan nilai tetap per domain
│   ├── services/         Akses API client-side per domain
│   ├── stores/           Global Svelte stores
│   ├── types/            Type definitions per domain
│   └── utils/            Helper murni dan reusable
└── routes/
    └── domain/
        ├── +page.svelte
        ├── [id]/
        │   └── +page.svelte
        └── _components/  Komponen khusus halaman domain
```

Komponen yang hanya digunakan oleh satu halaman ditempatkan di folder
`_components` terdekat. Komponen lintas halaman ditempatkan di `src/lib/components`.

## Konvensi Kode

- Gunakan indentasi 2 spasi, tanpa tab.
- Gunakan TypeScript pada setiap script Svelte: `<script lang="ts">`.
- Gunakan `type`, bukan `interface`, untuk bentuk data aplikasi.
- Hindari `any`; gunakan type domain, generic, `unknown`, atau type guard.
- Gunakan PascalCase untuk file komponen Svelte.
- Gunakan camelCase untuk fungsi dan variabel.
- Gunakan kebab-case untuk file helper TypeScript yang bukan domain service.
- Gunakan Svelte 5 runes: `$props`, `$state`, `$derived`, dan `$effect`.
- Gunakan callback props sebagai pengganti `createEventDispatcher`.
- Gunakan `Snippet` dan `{@render ...}` sebagai pengganti `<slot>`.
- Gunakan event attributes seperti `onclick`, `oninput`, dan `onsubmit`.
- Import type dengan `import type`.
- Re-export API publik folder melalui `index.ts`.

## Menambah Halaman

Jaga `+page.svelte` sebagai entry point yang tipis. State dan template besar dapat
dipindahkan ke komponen client di folder `_components`.

```svelte
<script lang="ts">
  import DomainPageClient from './_components/DomainPageClient.svelte';
</script>

<svelte:head>
  <title>Domain - Indogreen</title>
</svelte:head>

<DomainPageClient />
```

Komponen halaman menggunakan service, utility, dan komponen UI yang sudah tersedia:

```svelte
<script lang="ts">
  import { fetchDomains } from '$lib/services';
  import { extractApiErrors, showError } from '$lib/utils';
  import LoadingState from '$lib/components/common/LoadingState.svelte';

  let items = $state<Domain[]>([]);
  let loading = $state(true);
  let error = $state('');

  async function loadItems(): Promise<void> {
    loading = true;

    try {
      items = await fetchDomains();
      error = '';
    } catch (caughtError: unknown) {
      error = extractApiErrors(caughtError);
      showError(error);
    } finally {
      loading = false;
    }
  }

  $effect(() => {
    void loadItems();
  });
</script>
```

Aturan halaman:

- Gunakan `_components/FilterBar.svelte`, `TableView.svelte`, atau `ListView.svelte`
  ketika template mulai besar.
- Simpan state domain di orchestrator terdekat.
- Jangan memanggil `axiosClient` langsung dari page component.
- Gunakan komponen loading, empty state, pagination, toast, dan confirm yang tersedia.
- Pertahankan permission check pada layer UI dan kontrak API yang sudah ada.

## Menambah Service

Buat service per domain di `src/lib/services`, lalu re-export dari
`src/lib/services/index.ts`.

```typescript
import axiosClient from '$lib/axiosClient';
import type { Domain, DomainForm, PaginatedResponse } from '$lib/types';

/** Mengambil daftar domain dari API. */
export async function fetchDomains(): Promise<PaginatedResponse<Domain>> {
  const response = await axiosClient.get<PaginatedResponse<Domain>>('/domains');
  return response.data;
}

/** Membuat data domain baru. */
export async function createDomain(form: DomainForm): Promise<Domain> {
  const response = await axiosClient.post<{ data: Domain }>('/domains', form);
  return response.data.data;
}
```

Aturan service:

- Gunakan instance `axiosClient` yang sudah ada.
- Gunakan request dan response type yang eksplisit.
- Lempar error ke caller; jangan menampilkan toast, melakukan navigasi, atau mengakses DOM.
- Pindahkan builder `FormData` yang reusable ke `src/lib/utils/form-data.ts`.
- Tambahkan JSDoc singkat pada fungsi publik.

## Menambah Komponen UI

Komponen UI reusable ditempatkan di `src/lib/components/ui`. Komponen shared yang
memahami kebutuhan bisnis ditempatkan di `src/lib/components/common`.

```svelte
<script lang="ts">
  import type { Snippet } from 'svelte';

  /** Props untuk komponen reusable. */
  let {
    label,
    disabled = false,
    children
  }: {
    label: string;
    disabled?: boolean;
    children?: Snippet;
  } = $props();
</script>

<button type="button" {disabled} aria-label={label}>
  {@render children?.()}
</button>
```

Aturan komponen:

- Semua props harus fully typed.
- Gunakan `$bindable()` hanya untuk nilai yang memang mendukung `bind:`.
- Teks bisnis diterima melalui props bila komponen digunakan lintas domain.
- Pertahankan dukungan dark mode dan responsive layout.
- Gunakan elemen HTML semantik dan label aksesibilitas.
- Re-export komponen publik dari barrel `index.ts`.

## Utility Terpusat

- Badge status: `src/lib/utils/badges.ts`
- Format tanggal, uang, dan file: `src/lib/utils/formatters.ts`
- Pesan error API: `src/lib/utils/errors.ts`
- FormData: `src/lib/utils/form-data.ts`
- Body scroll lock: `src/lib/utils/scroll-lock.ts`
- Toast: `src/lib/utils/toast.ts`
- Confirm dialog: `src/lib/components/common/ConfirmDialog.svelte`

Jangan membuat ulang helper tersebut di page atau component.

## Verifikasi

Jalankan pemeriksaan berikut sebelum commit:

```bash
npm run lint
npm run check
npm run build
```

Untuk perubahan UI, jalankan aplikasi dan periksa alur terkait pada mode terang,
mode gelap, serta viewport desktop dan mobile.
