<script lang="ts">
  import Pagination from '$lib/components/Pagination.svelte';
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import { formatNumber, type Item } from '$lib/inventory';

  /** Item table with stock status, actions, and pagination. */
  let {
    items,
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
    items: Item[];
    loading: boolean;
    error: string;
    currentPage: number;
    lastPage: number;
    totalItems: number;
    perPage: number;
    perPageOptions: number[];
    canUpdate: boolean;
    canDelete: boolean;
    onDetail: (item: Item) => void;
    onEdit: (item: Item) => void;
    onDelete: (item: Item) => void;
    onPageChange: (page: number) => void;
    onPerPageChange: (perPage: number) => void;
  } = $props();

  function totalStock(item: Item): number {
    return (item.inventories ?? []).reduce(
      (sum, inventory) => sum + Number(inventory.quantity ?? 0),
      0
    );
  }

  function stockBadgeClasses(item: Item): string {
    return totalStock(item) <= item.minimum_stock
      ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
      : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
  }
</script>

<div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-black">
  <div class="overflow-x-auto">
    <table class="w-full min-w-[980px] divide-y divide-gray-200 dark:divide-gray-800">
      <thead class="bg-gray-50 dark:bg-neutral-900">
        <tr>
          {#each ['Item', 'Kategori', 'Unit', 'Stok', 'Minimum', 'Aksi'] as heading, index (heading)}
            <th
              class="px-4 py-3 text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300 {index >
              2
                ? 'text-right'
                : 'text-left'}"
            >
              {heading}
            </th>
          {/each}
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        {#if loading}
          <tr
            ><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Memuat item...</td
            ></tr
          >
        {:else if error}
          <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-red-600">{error}</td></tr>
        {:else if items.length === 0}
          <tr
            ><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada item.</td
            ></tr
          >
        {:else}
          {#each items as item (item.id)}
            <tr class="hover:bg-gray-50 dark:hover:bg-neutral-950">
              <td class="px-4 py-4">
                <div class="font-semibold text-gray-900 dark:text-gray-100">{item.name}</div>
                <div class="mt-1 font-mono text-xs text-gray-500 dark:text-gray-400">
                  {item.sku}
                </div>
              </td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                {item.category?.name ?? '-'}
              </td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{item.unit}</td>
              <td class="px-4 py-4 text-right">
                <span
                  class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {stockBadgeClasses(
                    item
                  )}"
                >
                  {formatNumber(totalStock(item))}
                </span>
              </td>
              <td class="px-4 py-4 text-right text-sm font-medium text-gray-700 dark:text-gray-300">
                {formatNumber(item.minimum_stock)}
              </td>
              <td class="px-4 py-4 text-right">
                <div class="inline-flex w-full justify-end">
                  <RowActionButtons
                    label={item.name}
                    canEdit={canUpdate}
                    {canDelete}
                    onDetail={() => onDetail(item)}
                    onEdit={() => onEdit(item)}
                    onDelete={() => onDelete(item)}
                  />
                </div>
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
