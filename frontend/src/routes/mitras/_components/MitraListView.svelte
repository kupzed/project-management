<script lang="ts">
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import type { Snippet } from 'svelte';
  import type { Mitra } from '$lib/types';
  import MitraCategoryBadges from './MitraCategoryBadges.svelte';

  /**
   * List/card view for mitras.
   */
  let {
    mitras,
    canUpdate = false,
    canDelete = false,
    onOpenDetail,
    onEdit,
    onDelete,
    footer
  }: {
    mitras: Mitra[];
    canUpdate?: boolean;
    canDelete?: boolean;
    onOpenDetail: (mitra: Mitra) => void;
    onEdit: (mitra: Mitra) => void;
    onDelete: (id: number) => void;
    footer?: Snippet;
  } = $props();

  function truncate(value: string | null | undefined, length: number): string {
    if (!value) return '-';
    return value.length > length ? `${value.slice(0, length)}...` : value;
  }
</script>

<div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
  <ul class="divide-y divide-gray-200 dark:divide-gray-700">
    {#each mitras as mitra (mitra.id)}
      <li>
        <a
          href={`/mitras/${mitra.id}`}
          class="block px-4 py-4 hover:bg-gray-50 sm:px-6 dark:hover:bg-neutral-950"
        >
          <div class="flex items-center justify-between gap-3">
            <p class="truncate text-sm font-medium text-indigo-600 dark:text-indigo-400">
              {mitra.nama}
            </p>
            <div class="ml-2 flex flex-shrink-0">
              <MitraCategoryBadges {mitra} />
            </div>
          </div>
          <div class="mt-2 sm:flex sm:justify-between">
            <div class="sm:flex">
              <p class="flex items-center text-sm text-gray-500 dark:text-gray-300">
                {truncate(mitra.alamat, 100)}
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
              <p>Email: {mitra.email || '-'}</p>
            </div>
          </div>
        </a>
        <div class="flex justify-end px-4 py-2 sm:px-6">
          <RowActionButtons
            label={mitra.nama}
            canEdit={canUpdate}
            {canDelete}
            onDetail={() => onOpenDetail(mitra)}
            onEdit={() => onEdit(mitra)}
            onDelete={() => onDelete(mitra.id)}
          />
        </div>
      </li>
    {/each}
  </ul>
  {#if footer}
    {@render footer()}
  {/if}
</div>
