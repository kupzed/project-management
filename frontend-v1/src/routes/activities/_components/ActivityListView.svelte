<script lang="ts">
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import { formatDate } from '$lib/utils/formatters';
  import type { Activity } from '$lib/types';

  /**
   * List/card view for activities.
   */
  let {
    activities,
    canUpdate = false,
    canDelete = false,
    onOpenDetail,
    onEdit,
    onDelete
  }: {
    activities: Activity[];
    canUpdate?: boolean;
    canDelete?: boolean;
    onOpenDetail: (activity: Activity) => void;
    onEdit: (activity: Activity) => void;
    onDelete: (activityId: number) => void;
  } = $props();
</script>

<div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
  <ul class="divide-y divide-gray-200 dark:divide-gray-700">
    {#each activities as activity (activity.id)}
      <li>
        <a
          href={`/activities/${activity.id}`}
          class="block px-4 py-4 hover:bg-gray-50 sm:px-6 dark:hover:bg-neutral-950"
        >
          <div class="flex items-center justify-between gap-3">
            <p class="truncate text-sm font-medium text-indigo-600 dark:text-indigo-400">
              {activity.name}
            </p>
            <div class="ml-2 flex flex-shrink-0">
              <span
                class="inline-flex rounded-full bg-gray-300 px-2 text-xs leading-5 font-semibold text-gray-900 dark:bg-gray-700 dark:text-gray-100"
              >
                {activity.kategori}
              </span>
            </div>
          </div>
          <div class="mt-2 sm:flex sm:justify-between">
            <div class="sm:flex">
              <p class="flex items-center text-sm text-gray-500 dark:text-gray-300">
                Project: {activity.project?.name || '-'}
                | Jenis: {activity.jenis}
                {#if (activity.jenis === 'Vendor' || activity.jenis === 'Customer') && activity.mitra}
                  | {activity.jenis}: {activity.mitra.nama}
                {/if}
                | From: {activity.from || '-'} | Deskripsi: {activity.short_desc || '-'}
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
              <p>
                Aktivitas: {activity.activity_date
                  ? formatDate(activity.activity_date, 'long')
                  : '-'}
              </p>
            </div>
          </div>
        </a>
        <div class="flex justify-end px-4 py-2 sm:px-6">
          <RowActionButtons
            label={activity.name}
            canEdit={canUpdate}
            {canDelete}
            onDetail={() => onOpenDetail(activity)}
            onEdit={() => onEdit(activity)}
            onDelete={() => onDelete(activity.id)}
          />
        </div>
      </li>
    {/each}
  </ul>
</div>
