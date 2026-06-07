<script lang="ts">
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import StatusBadge from '$lib/components/ui/StatusBadge.svelte';
  import { formatDate } from '$lib/utils/formatters';
  import type { Snippet } from 'svelte';
  import type { Activity, Project } from '$lib/types';
  import ProjectActivityTimeline from './ProjectActivityTimeline.svelte';

  /**
   * List/card view for projects, including expandable activity timelines.
   */
  let {
    projects,
    openActivities,
    canViewActivity = false,
    canUpdate = false,
    canDelete = false,
    onToggleActivities,
    onOpenDetail,
    onOpenActivityDetail,
    onEdit,
    onDelete,
    footer
  }: {
    projects: Project[];
    openActivities: Record<number, boolean>;
    canViewActivity?: boolean;
    canUpdate?: boolean;
    canDelete?: boolean;
    onToggleActivities: (projectId: number) => void;
    onOpenDetail: (project: Project) => void;
    onOpenActivityDetail: (activity: Activity, project: Project) => void;
    onEdit: (project: Project) => void;
    onDelete: (projectId: number) => void;
    footer?: Snippet;
  } = $props();

  function truncate(value: string | null | undefined, length: number): string {
    if (!value) return '-';
    return value.length > length ? `${value.slice(0, length)}...` : value;
  }
</script>

<div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
  <ul class="divide-y divide-gray-200 dark:divide-gray-700">
    {#each projects as project (project.id)}
      <li>
        <a
          href={`/projects/${project.id}`}
          class="block px-4 py-4 hover:bg-gray-50 sm:px-6 dark:hover:bg-neutral-950"
        >
          <div class="flex items-center justify-between gap-3">
            <p class="truncate text-sm font-medium text-indigo-600 dark:text-indigo-400">
              {project.name}
            </p>
            <div class="ml-2 flex flex-shrink-0 flex-wrap gap-2">
              <StatusBadge status={project.status} />
              {#if project.is_cert_projects}
                <span
                  class="inline-flex rounded-full bg-purple-100 px-2 text-xs leading-5 font-semibold text-purple-800 dark:bg-purple-900 dark:text-purple-200"
                >
                  Certificate
                </span>
              {/if}
            </div>
          </div>
          <div class="mt-2 sm:flex sm:justify-between">
            <div class="sm:flex">
              <p class="flex items-center text-sm text-gray-500 dark:text-gray-300">
                Customer: {project.mitra?.nama || '-'} | Deskripsi: {truncate(
                  project.description,
                  50
                )}
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
              <p>Mulai: {project.start_date ? formatDate(project.start_date, 'long') : '-'}</p>
            </div>
          </div>
        </a>

        <div class="flex justify-end gap-3 px-4 py-2 sm:px-6">
          {#if canViewActivity}
            <button
              type="button"
              onclick={() => onToggleActivities(project.id)}
              class="inline-flex items-center gap-1.5 rounded-md border border-transparent bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <rect x="3" y="5" width="6" height="6" rx="1"></rect>
                <path d="m3 17 2 2 4-4"></path>
                <path d="M13 6h8"></path>
                <path d="M13 12h8"></path>
                <path d="M13 18h8"></path>
              </svg>
              Kegiatan
            </button>
          {/if}
          <RowActionButtons
            label={project.name}
            canEdit={canUpdate}
            {canDelete}
            onDetail={() => onOpenDetail(project)}
            onEdit={() => onEdit(project)}
            onDelete={() => onDelete(project.id)}
          />
        </div>

        {#if canViewActivity && openActivities[project.id]}
          <div
            class="mt-0 border-t border-gray-100 bg-gray-50/50 p-5 dark:border-neutral-800 dark:bg-neutral-900/30"
          >
            <ProjectActivityTimeline
              {project}
              title="Riwayat Kegiatan Proyek"
              emptyText="Tidak ada kegiatan tercatat"
              onOpenActivity={onOpenActivityDetail}
            />
          </div>
        {/if}
      </li>
    {/each}
  </ul>
  {#if footer}
    {@render footer()}
  {/if}
</div>
