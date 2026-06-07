<script lang="ts">
  import SearchInput from '$lib/components/ui/SearchInput.svelte';
  import type { ProjectKategori, ProjectStatus } from '$lib/types';

  /**
   * Project list filters. Bind filter props in the page client and react through `onFilter`.
   */
  let {
    search = $bindable(''),
    statusFilter = $bindable('' as ProjectStatus | ''),
    kategoriFilter = $bindable('' as ProjectKategori | ''),
    certProjectFilter = $bindable(false),
    projectStatuses = [],
    projectKategoris = [],
    canCreate = false,
    onFilter,
    onCreate
  }: {
    search?: string;
    statusFilter?: ProjectStatus | '';
    kategoriFilter?: ProjectKategori | '';
    certProjectFilter?: boolean;
    projectStatuses?: ProjectStatus[];
    projectKategoris?: ProjectKategori[];
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
      bind:value={statusFilter}
      onchange={applyFilter}
      class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900
             sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Status: Semua</option>
      {#each projectStatuses as status (status)}
        <option value={status}>{status}</option>
      {/each}
    </select>

    <select
      bind:value={kategoriFilter}
      onchange={applyFilter}
      class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900
             sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Kategori: Semua</option>
      {#each projectKategoris as kategori (kategori)}
        <option value={kategori}>{kategori}</option>
      {/each}
    </select>

    <div
      class="flex items-center space-x-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900
             dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <input
        type="checkbox"
        id="cert_project_filter"
        bind:checked={certProjectFilter}
        onchange={applyFilter}
        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700"
      />
      <label
        for="cert_project_filter"
        class="text-sm whitespace-nowrap text-gray-900 dark:text-gray-100"
      >
        Certificate
      </label>
    </div>
  </div>

  <div class="w-full flex-grow sm:w-auto">
    <SearchInput bind:value={search} placeholder="Cari project..." onSearch={applyFilter} />
  </div>

  {#if canCreate}
    <button
      type="button"
      onclick={onCreate}
      class="w-full rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm
             hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none
             sm:w-auto dark:focus:ring-offset-gray-800"
    >
      Tambah Project
    </button>
  {/if}
</div>
