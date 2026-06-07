<script lang="ts">
  import { formatDate } from '$lib/utils/formatters';
  import type { Activity, Project } from '$lib/types';

  /**
   * Timeline of activities attached to a project row.
   */
  let {
    project,
    title,
    emptyText = 'Belum ada aktivitas tercatat untuk proyek ini.',
    onOpenActivity
  }: {
    project: Project;
    title?: string;
    emptyText?: string;
    onOpenActivity: (activity: Activity, project: Project) => void;
  } = $props();
</script>

{#if title}
  <h4 class="mb-5 text-[10px] font-bold tracking-widest text-gray-500 uppercase">{title}</h4>
{/if}

<div class="relative pr-4 pl-8">
  <div class="absolute top-0 bottom-0 left-[15px] w-[2px] bg-gray-200 dark:bg-neutral-800"></div>

  <div class="space-y-7">
    {#if !project.activities || project.activities.length === 0}
      <p class="py-2 text-xs text-gray-500 italic">{emptyText}</p>
    {:else}
      {#each project.activities as activity (activity.id)}
        <div class="relative">
          <div
            class="absolute top-1.5 -left-[21px] h-4 w-4 rounded-full border-2 border-white bg-emerald-500 ring-2 ring-emerald-500/20 dark:border-black"
          ></div>

          <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
            <div class="min-w-[130px] flex-shrink-0">
              <span class="text-xs font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                {activity.activity_date ? formatDate(activity.activity_date, 'long') : '-'}
              </span>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  onclick={() => onOpenActivity(activity, project)}
                  class="text-left text-sm leading-tight font-bold text-gray-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                >
                  {activity.name}
                </button>
                <span
                  class="inline-flex rounded-full bg-gray-300 px-2 py-0.5 text-[10px] font-medium text-gray-900 dark:bg-gray-700 dark:text-gray-100"
                >
                  {activity.kategori || 'Umum'}
                </span>
              </div>
              {#if activity.mitra}
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Jenis:
                  <span class="font-medium text-gray-700 dark:text-gray-300"
                    >{activity.jenis || '-'}</span
                  >
                  {#if (activity.jenis === 'Customer' || activity.jenis === 'Vendor') && activity.mitra}
                    | Mitra:
                    <span class="font-medium text-gray-700 dark:text-gray-300"
                      >{activity.mitra.nama || '-'}</span
                    >
                  {/if}
                </p>
              {/if}
            </div>
          </div>
        </div>
      {/each}
    {/if}
  </div>
</div>
