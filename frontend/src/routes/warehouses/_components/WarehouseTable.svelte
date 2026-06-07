<script lang="ts">
  import Pagination from '$lib/components/Pagination.svelte';
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import { formatNumber, type Warehouse } from '$lib/inventory';

  /** Warehouse table with actions and pagination. */
  let {
    warehouses,
    loading,
    error,
    currentPage,
    lastPage,
    totalItems,
    perPage,
    perPageOptions,
    canUpdate,
    canDelete,
    onDetail,
    onEdit,
    onDelete,
    onPageChange,
    onPerPageChange
  }: {
    warehouses: Warehouse[];
    loading: boolean;
    error: string;
    currentPage: number;
    lastPage: number;
    totalItems: number;
    perPage: number;
    perPageOptions: number[];
    canUpdate: boolean;
    canDelete: boolean;
    onDetail: (warehouse: Warehouse) => void;
    onEdit: (warehouse: Warehouse) => void;
    onDelete: (warehouse: Warehouse) => void;
    onPageChange: (page: number) => void;
    onPerPageChange: (perPage: number) => void;
  } = $props();
</script>

<div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-black">
  <div class="overflow-x-auto">
    <table class="w-full min-w-[860px] divide-y divide-gray-200 dark:divide-gray-800">
      <thead class="bg-gray-50 dark:bg-neutral-900">
        <tr>
          <th
            class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >Gudang</th
          >
          <th
            class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >Lokasi</th
          >
          <th
            class="px-4 py-3 text-right text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >Item Stok</th
          >
          <th
            class="px-4 py-3 text-right text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >Aksi</th
          >
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        {#if loading}
          <tr
            ><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500"
              >Memuat gudang...</td
            ></tr
          >
        {:else if error}
          <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-red-600">{error}</td></tr>
        {:else if warehouses.length === 0}
          <tr
            ><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500"
              >Belum ada gudang.</td
            ></tr
          >
        {:else}
          {#each warehouses as warehouse (warehouse.id)}
            <tr class="hover:bg-gray-50 dark:hover:bg-neutral-950">
              <td class="px-4 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                {warehouse.name}
              </td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                {warehouse.location || '-'}
              </td>
              <td
                class="px-4 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-200"
              >
                {formatNumber(warehouse.inventories_count ?? 0)}
              </td>
              <td class="px-4 py-4 text-right">
                <RowActionButtons
                  label={warehouse.name}
                  canEdit={canUpdate}
                  {canDelete}
                  onDetail={() => onDetail(warehouse)}
                  onEdit={() => onEdit(warehouse)}
                  onDelete={() => onDelete(warehouse)}
                />
              </td>
            </tr>
          {/each}
        {/if}
      </tbody>
    </table>
  </div>
  <Pagination
    {currentPage}
    {lastPage}
    {totalItems}
    itemsPerPage={perPage}
    {perPageOptions}
    {onPageChange}
    {onPerPageChange}
  />
</div>
