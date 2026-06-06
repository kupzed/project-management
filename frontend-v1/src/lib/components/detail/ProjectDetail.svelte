<script lang="ts">
  import StatusBadge from '$lib/components/ui/StatusBadge.svelte';
  import { formatDate } from '$lib/utils/formatters';
  import type { Project } from '$lib/types';

  /**
   * Readonly project detail payload rendered inside project detail pages.
   */
  let { project = null }: { project?: Project | null } = $props();
</script>

{#if project}
  <div
    class="overflow-hidden border border-gray-200 bg-white shadow sm:rounded-md dark:border-neutral-800 dark:bg-black"
  >
    <dl class="divide-y divide-gray-100 dark:divide-gray-800">
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Nama Project</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {project.name}
        </dd>
      </div>
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Customer</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {#if project.mitra}<a
              href={`/mitras/${project.mitra.id}`}
              class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
              >{project.mitra.nama}</a
            >{:else}-{/if}
        </dd>
      </div>
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Kategori</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {project.kategori || '-'}
        </dd>
      </div>
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Lokasi</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {project.lokasi || '-'}
        </dd>
      </div>
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Status</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          <StatusBadge status={project.status} />
        </dd>
      </div>
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">No. PO</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {project.no_po || '-'}
        </dd>
      </div>
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">No. SO</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {project.no_so || '-'}
        </dd>
      </div>
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Deskripsi</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {project.description}
        </dd>
      </div>
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Tanggal Mulai</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {formatDate(project.start_date, 'long')}
        </dd>
      </div>
      <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 dark:bg-black">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Tanggal Selesai</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">
          {#if project.finish_date}{formatDate(project.finish_date, 'long')}{:else}<span
              class="text-gray-500 dark:text-gray-400">Tanggal belum ditambahkan</span
            >{/if}
        </dd>
      </div>
    </dl>
  </div>
{/if}
