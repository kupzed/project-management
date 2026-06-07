<script lang="ts">
  import Pagination from '$lib/components/Pagination.svelte';
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
                <div class="inline-flex items-center gap-2">
                  {#if canUpdate}
                    <button
                      type="button"
                      onclick={() => onEdit(item)}
                      class="rounded-md p-2 text-blue-600 hover:bg-blue-50 hover:text-blue-800 dark:text-blue-400 dark:hover:bg-blue-950"
                      title="Edit"
                      aria-label="Edit item"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                      </svg>
                    </button>
                  {/if}
                  {#if canDelete}
                    <button
                      type="button"
                      onclick={() => onDelete(item)}
                      class="rounded-md p-2 text-red-600 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-950"
                      title="Hapus"
                      aria-label="Hapus item"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3 0V5a2 2 0 012-2h0a2 2 0 012 2v2"
                        />
                      </svg>
                    </button>
                  {/if}
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
