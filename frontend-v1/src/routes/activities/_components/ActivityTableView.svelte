<script lang="ts">
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import { formatDate } from '$lib/utils/formatters';
  import type { Snippet } from 'svelte';
  import type { Activity } from '$lib/types';

  /**
   * Table view for activity rows.
   */
  let {
    activities,
    canUpdate = false,
    canDelete = false,
    onOpenDetail,
    onEdit,
    onDelete,
    footer
  }: {
    activities: Activity[];
    canUpdate?: boolean;
    canDelete?: boolean;
    onOpenDetail: (activity: Activity) => void;
    onEdit: (activity: Activity) => void;
    onDelete: (activityId: number) => void;
    footer?: Snippet;
  } = $props();

  function truncate(value: string | null | undefined, length: number): string {
    if (!value) return '-';
    return value.length > length ? `${value.slice(0, length)}...` : value;
  }
</script>

<div class="mt-4 rounded-lg bg-white shadow-md dark:bg-black">
  <div class="overflow-x-auto">
    <table class="min-w-full table-fixed divide-y divide-gray-300 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-neutral-900">
        <tr>
          <th
            class="w-[10rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Tanggal Aktivitas</th
          >
          <th
            class="w-[23rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Nama Aktivitas</th
          >
          <th
            class="w-[15rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Project</th
          >
          <th
            class="w-[10rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Kategori</th
          >
          <th
            class="w-[8rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Jenis</th
          >
          <th
            class="w-[14rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Mitra</th
          >
          <th
            class="w-[7.5rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Aksi</th
          >
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-black">
        {#each activities as activity (activity.id)}
          <tr>
            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
              {activity.activity_date ? formatDate(activity.activity_date) : '-'}
            </td>
            <td class="px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
              <a
                href={`/activities/${activity.id}`}
                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                title="Detail"
              >
                {activity.name}
              </a>
              <br />
              <span class="text-xs text-gray-500 dark:text-gray-400"
                >From: {activity.from || '-'} | {activity.short_desc || '-'}</span
              >
            </td>
            <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
              {truncate(activity.project?.name, 25)}
            </td>
            <td class="px-3 py-4 text-sm">
              <span
                class="inline-flex rounded-full bg-gray-300 px-2 text-xs leading-5 font-semibold text-gray-900 dark:bg-gray-700 dark:text-gray-100"
              >
                {activity.kategori}
              </span>
            </td>
            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300"
              >{activity.jenis}</td
            >
            <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
              {#if (activity.jenis === 'Vendor' || activity.jenis === 'Customer') && activity.mitra}
                {truncate(activity.mitra.nama, 25)}
              {:else}
                -
              {/if}
            </td>
            <td class="px-3 py-4 text-left text-sm font-medium whitespace-nowrap">
              <RowActionButtons
                label={activity.name}
                canEdit={canUpdate}
                {canDelete}
                onDetail={() => onOpenDetail(activity)}
                onEdit={() => onEdit(activity)}
                onDelete={() => onDelete(activity.id)}
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
