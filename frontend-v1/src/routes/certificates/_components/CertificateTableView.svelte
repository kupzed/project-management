<script lang="ts">
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import StatusBadge from '$lib/components/ui/StatusBadge.svelte';
  import { formatDate } from '$lib/utils/formatters';
  import type { Snippet } from 'svelte';
  import type { Certificate } from '$lib/types';

  /**
   * Table view for the certificate list.
   */
  let {
    items,
    canUpdate = false,
    canDelete = false,
    onOpenDetail,
    onEdit,
    onDelete,
    footer
  }: {
    items: Certificate[];
    canUpdate?: boolean;
    canDelete?: boolean;
    onOpenDetail: (item: Certificate) => void;
    onEdit: (item: Certificate) => void;
    onDelete: (id: number) => void;
    footer?: Snippet;
  } = $props();
</script>

<div class="bg-white shadow-md sm:rounded-lg dark:bg-black">
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-neutral-900">
        <tr>
          <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Nama</th
          >
          <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >No. Sertifikat</th
          >
          <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Barang Sertifikat</th
          >
          <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Status</th
          >
          <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Terbit</th
          >
          <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Expired</th
          >
          <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Aksi</th
          >
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-black">
        {#each items as item (item.id)}
          <tr>
            <td
              class="px-3 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-gray-100"
            >
              <a
                href={`/certificates/${item.id}`}
                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                >{item.name}</a
              ><br />
              <span class="text-xs text-gray-500 dark:text-gray-400"
                >{item.project?.name || '-'}</span
              >
            </td>
            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
              {item.no_certificate}
            </td>
            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
              {item.barang_certificate?.name || '-'}
            </td>
            <td class="px-3 py-4 text-sm whitespace-nowrap">
              <StatusBadge status={item.status} type="certificate" />
            </td>
            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
              {item.date_of_issue ? formatDate(item.date_of_issue) : '-'}
            </td>
            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
              {item.date_of_expired ? formatDate(item.date_of_expired) : '-'}
            </td>
            <td class="relative px-3 py-4 text-sm whitespace-nowrap">
              <RowActionButtons
                label={item.name}
                canEdit={canUpdate}
                {canDelete}
                onDetail={() => onOpenDetail(item)}
                onEdit={() => onEdit(item)}
                onDelete={() => onDelete(item.id)}
              />
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
  {#if footer}
    {@render footer()}
  {/if}
</div>
