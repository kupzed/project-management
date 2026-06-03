<script lang="ts">
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import StatusBadge from '$lib/components/ui/StatusBadge.svelte';
  import { formatDate } from '$lib/utils/formatters';
  import type { Activity, Project } from '$lib/types';
  import ProjectActivityTimeline from './ProjectActivityTimeline.svelte';

  /**
   * Table view for the project list.
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
    onDelete
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
            scope="col"
            class="w-[22rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Nama Project</th
          >
          <th
            scope="col"
            class="w-[13rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Lokasi</th
          >
          <th
            scope="col"
            class="w-[6rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Tahun</th
          >
          <th
            scope="col"
            class="w-[10rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Kategori</th
          >
          <th
            scope="col"
            class="w-[13rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Status</th
          >
          <th
            scope="col"
            class="w-[10rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Dilaksanakan</th
          >
          <th
            scope="col"
            class="w-[8rem] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
            >Aksi</th
          >
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-black">
        {#each projects as project (project.id)}
          <tr>
            <td class="px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
              <a
                href={`/projects/${project.id}`}
                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                title="Detail"
              >
                {project.name}
              </a><br />
              <span class="text-xs text-gray-500 dark:text-gray-400"
                >{project.mitra?.nama || '-'}</span
              >
            </td>
            <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-300"
              >{truncate(project.lokasi, 40)}</td
            >
            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
              {project.start_date
                ? new Date(project.start_date).toLocaleDateString('id-ID', { year: 'numeric' })
                : '-'}
            </td>
            <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-300"
              >{project.kategori || '-'}</td
            >
            <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
              <div class="flex flex-wrap items-center gap-2">
                <StatusBadge status={project.status} />
                {#if project.is_cert_projects}
                  <span
                    class="inline-flex rounded-full bg-purple-100 px-2 text-xs leading-5 font-semibold text-purple-800 dark:bg-purple-900 dark:text-purple-200"
                  >
                    Certificate
                  </span>
                {/if}
              </div>
            </td>
            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
              {project.start_date ? formatDate(project.start_date) : '-'}<br />
              {#if project.finish_date}{formatDate(project.finish_date)}{:else}-{/if}
            </td>
            <td class="px-3 py-4 text-left text-sm font-medium whitespace-nowrap">
              <div class="flex items-center gap-2">
                {#if canViewActivity}
                  <button
                    type="button"
                    onclick={() => onToggleActivities(project.id)}
                    class={openActivities[project.id]
                      ? 'text-emerald-600 transition-colors dark:text-emerald-400'
                      : 'text-gray-400 transition-colors hover:text-emerald-500'}
                    aria-label="Lihat Kegiatan"
                    title="Daftar Kegiatan"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="20"
                      height="20"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
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
            </td>
          </tr>

          {#if canViewActivity && openActivities[project.id]}
            <tr class="bg-gray-50/70 transition-all dark:bg-neutral-900/50">
              <td colspan="7" class="px-3 py-0">
                <div class="relative py-5 pr-4 pl-14">
                  <ProjectActivityTimeline
                    {project}
                    emptyText="Belum ada aktivitas tercatat untuk proyek ini."
                    onOpenActivity={onOpenActivityDetail}
                  />
                </div>
              </td>
            </tr>
          {/if}
        {/each}
      </tbody>
    </table>
  </div>
</div>
