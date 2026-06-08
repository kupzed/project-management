<script lang="ts">
  import SearchInput from '$lib/components/ui/SearchInput.svelte';
  import type { MitraCategory, MitraKategoriOption } from '$lib/types';

  /**
   * Mitra list filters. Parent owns state through bindable props.
   */
  let {
    search = $bindable(''),
    kategoriFilter = $bindable('' as MitraCategory | ''),
    kategoriOptions = [],
    canCreate = false,
    onFilter,
    onCreate
  }: {
    search?: string;
    kategoriFilter?: MitraCategory | '';
    kategoriOptions?: MitraKategoriOption[];
    canCreate?: boolean;
    onFilter?: () => void;
    onCreate?: () => void;
  } = $props();

  function applyFilter(): void {
    onFilter?.();
  }
</script>

<div
  class="mb-4 flex flex-col items-center justify-between space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4"
>
  <div class="flex w-full space-x-2 sm:w-auto">
    <select
      bind:value={kategoriFilter}
      onchange={applyFilter}
      class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900
             sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Kategori: Semua</option>
      {#each kategoriOptions as kategori (kategori.value)}
        <option value={kategori.value}>{kategori.label}</option>
      {/each}
    </select>
  </div>

  <div class="w-full flex-grow sm:w-auto">
    <SearchInput bind:value={search} placeholder="Cari mitra..." onSearch={applyFilter} />
  </div>

  {#if canCreate}
    <button
      type="button"
      onclick={onCreate}
      class="w-full rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm
             hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none
             sm:w-auto dark:focus:ring-offset-gray-800"
    >
      Tambah Mitra
    </button>
  {/if}
</div>
