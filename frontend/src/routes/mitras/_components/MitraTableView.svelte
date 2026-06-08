<script lang="ts">
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import type { Snippet } from 'svelte';
  import type { Mitra } from '$lib/types';
  import MitraCategoryBadges from './MitraCategoryBadges.svelte';

  /**
   * Table view for mitra list.
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

<div class="bg-white shadow-md sm:rounded-lg dark:bg-black">
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-neutral-900">
        <tr>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
          >
            Nama Mitra
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
          >
            Alamat
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
          >
            Kategori
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
          >
            Kontak
          </th>
          <th
            scope="col"
            class="relative px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
          >
            Aksi
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-black">
        {#each mitras as mitra (mitra.id)}
          <tr>
            <td
              class="px-3 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-gray-100"
            >
              <a
                href={`/mitras/${mitra.id}`}
                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                title="Detail"
              >
                {mitra.nama}
              </a>
              <br />
              <span class="text-xs text-gray-500 dark:text-gray-400">
                {mitra.email || '(email belum ditambahkan)'}
              </span>
            </td>
            <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
              <div class="max-w-xs truncate">{truncate(mitra.alamat, 40)}</div>
            </td>
            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
              <MitraCategoryBadges {mitra} />
            </td>
            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
              {mitra.kontak_1 || '-'}
              {#if mitra.kontak_1_nama}
                <br /><span class="text-xs text-gray-400">({mitra.kontak_1_nama})</span>
              {/if}
            </td>
            <td class="relative px-3 py-4 text-left text-sm font-medium whitespace-nowrap">
              <RowActionButtons
                label={mitra.nama}
                canEdit={canUpdate}
                {canDelete}
                onDetail={() => onOpenDetail(mitra)}
                onEdit={() => onEdit(mitra)}
                onDelete={() => onDelete(mitra.id)}
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
