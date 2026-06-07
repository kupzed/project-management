<script lang="ts">
  import type { BarangCertificate } from '$lib/types';

  /**
   * Props for read-only barang certificate details.
   */
  let {
    barangCertificates = null
  }: {
    barangCertificates?: BarangCertificate | null;
  } = $props();
</script>

{#if barangCertificates}
  <div
    class="overflow-hidden border border-gray-200 bg-white shadow sm:rounded-md dark:border-neutral-800 dark:bg-black"
  >
    <dl class="divide-y divide-gray-100 dark:divide-gray-800">
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Nama</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {barangCertificates.name}
        </dd>
      </div>

      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">No. Seri</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {barangCertificates.no_seri}
        </dd>
      </div>

      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Mitra</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {#if barangCertificates.mitra}
            <a
              href={`/mitras/${barangCertificates.mitra.id}`}
              class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
              >{barangCertificates.mitra.nama}</a
            >
          {:else}
            -
          {/if}
        </dd>
      </div>
    </dl>
  </div>
{/if}
