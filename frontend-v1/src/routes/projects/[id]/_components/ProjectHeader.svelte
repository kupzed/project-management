<script lang="ts">
  import StatusBadge from '$lib/components/ui/StatusBadge.svelte';
  import { formatDate } from '$lib/utils/formatters';
  import type { Project } from '$lib/types';

  /**
   * Header props for project identity and permission-gated project actions.
   */
  let {
    project,
    canUpdate,
    canDelete,
    onEdit,
    onDelete
  }: {
    project: Project;
    canUpdate: boolean;
    canDelete: boolean;
    onEdit?: () => void;
    onDelete?: () => void | Promise<void>;
  } = $props();
</script>

<div class="mb-4 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
  <div class="min-w-0 flex-1">
    <h2 class="break-words text-2xl leading-7 font-bold text-gray-900 sm:text-2xl dark:text-white">
      {project.name}
    </h2>
    <div class="my-2 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
      <div class="my-2 flex items-center text-sm text-gray-500 dark:text-gray-300">
        <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
          <path
            fill-rule="evenodd"
            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
            clip-rule="evenodd"
          ></path>
        </svg>
        Mulai: {formatDate(project.start_date, 'long')}
      </div>
      <div class="my-2 flex items-center text-sm">
        <StatusBadge status={project.status} />
      </div>
    </div>
  </div>
  <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
    {#if canUpdate}
      <button
        type="button"
        onclick={onEdit}
        class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:focus:ring-offset-gray-800"
      >
        Edit Project
      </button>
    {/if}
    {#if canDelete}
      <button
        type="button"
        onclick={onDelete}
        class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none dark:focus:ring-offset-gray-800"
      >
        Hapus Project
      </button>
    {/if}
  </div>
</div>
