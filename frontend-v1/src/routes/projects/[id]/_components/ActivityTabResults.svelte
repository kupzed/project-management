<script lang="ts">
  import Pagination from '$lib/components/Pagination.svelte';
  import EmptyState from '$lib/components/common/EmptyState.svelte';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { formatDate } from '$lib/utils/formatters';
  import type { Activity } from '$lib/types';
  import type { ViewToggleView } from '$lib/components/ui/ViewToggle.svelte';
  import RowActions from './RowActions.svelte';

  /** Activity result list/table with pagination and row actions. */
  let {
    loading,
    error,
    activities,
    view,
    currentPage,
    lastPage,
    totalItems,
    perPage,
    pageOptions,
    canUpdate,
    canDelete,
    onOpenDetail,
    onEdit,
    onDelete,
    onPageChange,
    onPerPageChange
  }: {
    loading: boolean;
    error: string;
    activities: Activity[];
    view: ViewToggleView;
    currentPage: number;
    lastPage: number;
    totalItems: number;
    perPage: number;
    pageOptions: number[];
    canUpdate: boolean;
    canDelete: boolean;
    onOpenDetail: (activity: Activity) => void;
    onEdit: (activity: Activity) => void;
    onDelete: (activityId: number) => void;
    onPageChange: (page: number) => void;
    onPerPageChange: (perPage: number) => void;
  } = $props();
</script>

{#if loading}
  <LoadingState label="Memuat aktivitas..." />
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if activities.length === 0}
  <div class="mt-4 overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
    <EmptyState title="Belum ada aktivitas untuk project ini." />
  </div>
{:else if view === 'list'}
  <div class="mt-4 overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
      {#each activities as activity (activity.id)}
        <li>
          <button
            type="button"
            class="block w-full cursor-pointer px-4 py-4 text-left hover:bg-gray-50 sm:px-6 dark:hover:bg-neutral-950"
            onclick={() => onOpenDetail(activity)}
          >
            <div class="flex items-center justify-between">
              <p class="truncate text-sm font-medium text-indigo-600 dark:text-indigo-400">
                {activity.name}
              </p>
              <span
                class="ml-2 inline-flex flex-shrink-0 rounded-full bg-gray-300 px-2 text-xs leading-5 font-semibold text-gray-900 dark:bg-gray-700 dark:text-gray-100"
                >{activity.kategori}</span
              >
            </div>
            <div class="mt-2 sm:flex sm:justify-between">
              <p class="text-sm text-gray-500 dark:text-gray-300">
                Jenis: {activity.jenis}{#if activity.mitra}
                  | Mitra: {activity.mitra.nama}{/if} | From: {activity.from || '-'} | Deskripsi: {activity.short_desc}
              </p>
              <p class="mt-2 text-sm text-gray-500 sm:mt-0 dark:text-gray-300">
                Aktivitas: {activity.activity_date
                  ? formatDate(activity.activity_date, 'long')
                  : '-'}
              </p>
            </div>
          </button>
          <div class="px-4 py-2 sm:px-6">
            <RowActions
              itemName={activity.name}
              {canUpdate}
              {canDelete}
              onDetail={() => onOpenDetail(activity)}
              onEdit={() => onEdit(activity)}
              onDelete={() => onDelete(activity.id)}
            />
          </div>
        </li>
      {/each}
    </ul>
    <Pagination
      {currentPage}
      {lastPage}
      {totalItems}
      itemsPerPage={perPage}
      perPageOptions={pageOptions}
      {onPageChange}
      {onPerPageChange}
    />
  </div>
{:else}
  <div class="mt-4 w-full min-w-0 overflow-hidden rounded-lg bg-white shadow-md dark:bg-black">
    <div class="w-full overflow-x-auto">
      <table
        class="w-full min-w-[920px] table-fixed divide-y divide-gray-300 lg:min-w-full dark:divide-gray-700"
      >
        <thead class="bg-gray-50 dark:bg-neutral-900"
          ><tr
            ><th
              class="w-32 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Tanggal Aktivitas</th
            ><th
              class="w-[34%] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Nama Aktivitas</th
            ><th
              class="w-36 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Kategori</th
            ><th
              class="w-28 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Jenis</th
            ><th
              class="w-[22%] px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Mitra</th
            ><th
              class="w-28 px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Aksi</th
            ></tr
          ></thead
        >
        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-black">
          {#each activities as activity (activity.id)}
            <tr
              ><td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300"
                >{activity.activity_date ? formatDate(activity.activity_date) : '-'}</td
              ><td class="px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100"
                ><button
                  type="button"
                  class="block text-left break-words text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                  onclick={() => onOpenDetail(activity)}>{activity.name}</button
                ><span
                  class="mt-1 block text-xs leading-5 break-words text-gray-500 dark:text-gray-400"
                  >From: {activity.from || '-'} | {activity.short_desc}</span
                ></td
              ><td class="px-3 py-4 text-sm"
                ><span
                  class="inline-flex rounded-full bg-gray-300 px-2 text-xs leading-5 font-semibold text-gray-900 dark:bg-gray-700 dark:text-gray-100"
                  >{activity.kategori}</span
                ></td
              ><td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300"
                >{activity.jenis}</td
              ><td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-300"
                >{activity.mitra?.nama ?? '-'}</td
              ><td class="px-3 py-4 text-sm font-medium whitespace-nowrap"
                ><RowActions
                  itemName={activity.name}
                  {canUpdate}
                  {canDelete}
                  onDetail={() => onOpenDetail(activity)}
                  onEdit={() => onEdit(activity)}
                  onDelete={() => onDelete(activity.id)}
                /></td
              ></tr
            >
          {/each}
        </tbody>
      </table>
    </div>
    <Pagination
      {currentPage}
      {lastPage}
      {totalItems}
      itemsPerPage={perPage}
      perPageOptions={pageOptions}
      {onPageChange}
      {onPerPageChange}
    />
  </div>
{/if}
