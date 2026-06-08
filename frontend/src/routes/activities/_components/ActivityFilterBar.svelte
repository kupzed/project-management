<script lang="ts">
  import SearchInput from '$lib/components/ui/SearchInput.svelte';
  import type { ActivityJenis, ActivityKategori } from '$lib/types';

  /**
   * Activity list filters. Parent owns state via bindable props.
   */
  let {
    search = $bindable(''),
    jenisFilter = $bindable('' as ActivityJenis | ''),
    kategoriFilter = $bindable('' as ActivityKategori | ''),
    activityJenisList = [],
    activityKategoriList = [],
    canCreate = false,
    onFilter,
    onCreate
  }: {
    search?: string;
    jenisFilter?: ActivityJenis | '';
    kategoriFilter?: ActivityKategori | '';
    activityJenisList?: ActivityJenis[];
    activityKategoriList?: ActivityKategori[];
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
      bind:value={jenisFilter}
      onchange={applyFilter}
      class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900
             sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Jenis: Semua</option>
      {#each activityJenisList as jenis (jenis)}
        <option value={jenis}>{jenis}</option>
      {/each}
    </select>
    <select
      bind:value={kategoriFilter}
      onchange={applyFilter}
      class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900
             sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Kategori: Semua</option>
      {#each activityKategoriList as kategori (kategori)}
        <option value={kategori}>{kategori}</option>
      {/each}
    </select>
  </div>

  <div class="w-full flex-grow sm:w-auto">
    <SearchInput bind:value={search} placeholder="Cari aktivitas..." onSearch={applyFilter} />
  </div>

  {#if canCreate}
    <button
      type="button"
      onclick={onCreate}
      class="w-full rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm
             hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none
             sm:w-auto dark:focus:ring-offset-gray-800"
    >
      Tambah Aktivitas
    </button>
  {/if}
</div>
