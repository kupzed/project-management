<script lang="ts">
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import StatusBadge from '$lib/components/ui/StatusBadge.svelte';
  import { formatDate } from '$lib/utils/formatters';
  import type { Snippet } from 'svelte';
  import type { Certificate } from '$lib/types';

  /**
   * List/card view for certificates.
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

<div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
  <ul class="divide-y divide-gray-200 dark:divide-gray-700">
    {#each items as item (item.id)}
      <li>
        <a
          href={`/certificates/${item.id}`}
          class="block px-4 py-4 hover:bg-gray-50 sm:px-6 dark:hover:bg-neutral-950"
        >
          <div class="flex items-center justify-between gap-3">
            <p class="truncate text-sm font-medium text-indigo-600 dark:text-indigo-400">
              {item.name}
            </p>
            <div class="ml-2 flex flex-shrink-0">
              <StatusBadge status={item.status} type="certificate" />
            </div>
          </div>
          <div class="mt-2 sm:flex sm:justify-between">
            <div class="sm:flex">
              <p class="flex items-center text-sm text-gray-500 dark:text-gray-300">
                Project: {item.project?.name || '-'} | Barang: {item.barang_certificate?.name ||
                  '-'} | No: {item.no_certificate}
              </p>
            </div>
            <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 dark:text-gray-300">
              <svg
                class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400"
                fill="currentColor"
                viewBox="0 0 20 20"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <p>Terbit: {item.date_of_issue ? formatDate(item.date_of_issue, 'long') : '-'}</p>
            </div>
          </div>
        </a>
        <div class="flex justify-end px-4 py-2 sm:px-6">
          <RowActionButtons
            label={item.name}
            canEdit={canUpdate}
            {canDelete}
            onDetail={() => onOpenDetail(item)}
            onEdit={() => onEdit(item)}
            onDelete={() => onDelete(item.id)}
          />
        </div>
      </li>
    {/each}
  </ul>
  {#if footer}
    {@render footer()}
  {/if}
</div>
