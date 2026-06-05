<script lang="ts">
  import type { Mitra } from '$lib/types';

  /**
   * Props for read-only Mitra detail rows.
   */
  let { mitra = null }: { mitra?: Mitra | null } = $props();

  function getKategoriBadgeColor(kategori: string) {
    // badge konsisten: tint di light, deep di dark
    const base = 'inline-flex rounded-full px-2 text-xs font-semibold leading-5';
    switch (kategori) {
      case 'Pribadi':
      case 'Perusahaan':
      case 'Customer':
      case 'Vendor':
        return `${base} bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200`;
      default:
        return `${base} bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200`;
    }
  }
</script>

{#if mitra}
  <div
    class="overflow-hidden border border-gray-200 bg-white shadow sm:rounded-md dark:border-neutral-800 dark:bg-black"
  >
    <dl class="divide-y divide-gray-100 dark:divide-gray-800">
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Nama</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {mitra.nama}
        </dd>
      </div>

      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Kategori</dt>
        <dd class="mt-1 space-x-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {#if mitra.is_pribadi}<span class={getKategoriBadgeColor('Pribadi')}>Pribadi</span>{/if}
          {#if mitra.is_perusahaan}<span class={getKategoriBadgeColor('Perusahaan')}
              >Perusahaan</span
            >{/if}
          {#if mitra.is_customer}<span class={getKategoriBadgeColor('Customer')}>Customer</span
            >{/if}
          {#if mitra.is_vendor}<span class={getKategoriBadgeColor('Vendor')}>Vendor</span>{/if}
        </dd>
      </div>

      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Alamat</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {mitra.alamat}
        </dd>
      </div>

      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Website</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {mitra.website || '-'}
        </dd>
      </div>

      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Email</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {mitra.email || '-'}
        </dd>
      </div>

      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Kontak 1</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {#if mitra.kontak_1}
            {mitra.kontak_1}<br />
            <span class="text-xs text-gray-500 dark:text-gray-400"
              >Nama: {mitra.kontak_1_nama} | Jabatan: {mitra.kontak_1_jabatan}</span
            >
          {:else}
            -
          {/if}
        </dd>
      </div>

      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Kontak 2</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {#if mitra.kontak_2}
            {mitra.kontak_2}<br />
            <span class="text-xs text-gray-500 dark:text-gray-400"
              >Nama: {mitra.kontak_2_nama} | Jabatan: {mitra.kontak_2_jabatan}</span
            >
          {:else}
            -
          {/if}
        </dd>
      </div>
    </dl>
  </div>
{/if}
