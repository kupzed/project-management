<script lang="ts">
  import Pagination from '$lib/components/Pagination.svelte';
  import EmptyState from '$lib/components/common/EmptyState.svelte';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import StatusBadge from '$lib/components/ui/StatusBadge.svelte';
  import { formatDate } from '$lib/utils/formatters';
  import type { Certificate } from '$lib/types';
  import type { ViewToggleView } from '$lib/components/ui/ViewToggle.svelte';
  import RowActions from './RowActions.svelte';

  /** Certificate result list/table with pagination and row actions. */
  let {
    loading,
    error,
    certificates,
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
    certificates: Certificate[];
    view: ViewToggleView;
    currentPage: number;
    lastPage: number;
    totalItems: number;
    perPage: number;
    pageOptions: number[];
    canUpdate: boolean;
    canDelete: boolean;
    onOpenDetail: (certificate: Certificate) => void;
    onEdit: (certificate: Certificate) => void;
    onDelete: (certificateId: number) => void;
    onPageChange: (page: number) => void;
    onPerPageChange: (perPage: number) => void;
  } = $props();
</script>

{#if loading}
  <LoadingState label="Memuat sertifikat..." />
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if certificates.length === 0}
  <div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
    <EmptyState title="Belum ada sertifikat untuk proyek ini." />
  </div>
{:else if view === 'list'}
  <div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
      {#each certificates as certificate (certificate.id)}
        <li>
          <button
            type="button"
            class="block w-full cursor-pointer px-4 py-4 text-left hover:bg-gray-50 sm:px-6 dark:hover:bg-neutral-950"
            onclick={() => onOpenDetail(certificate)}
          >
            <div class="flex items-center justify-between">
              <p class="truncate text-sm font-medium text-indigo-600 dark:text-indigo-400">
                {certificate.name}
              </p>
              <StatusBadge status={certificate.status} type="certificate" />
            </div>
            <div class="mt-2 sm:flex sm:justify-between">
              <p class="text-sm text-gray-500 dark:text-gray-300">
                Barang: {certificate.barang_certificate?.name || '-'} | No: {certificate.no_certificate}
              </p>
              <p class="mt-2 text-sm text-gray-500 sm:mt-0 dark:text-gray-300">
                Terbit: {certificate.date_of_issue
                  ? formatDate(certificate.date_of_issue, 'long')
                  : '-'}
              </p>
            </div>
          </button>
          <div class="px-4 py-2 sm:px-6">
            <RowActions
              itemName={certificate.name}
              {canUpdate}
              {canDelete}
              onDetail={() => onOpenDetail(certificate)}
              onEdit={() => onEdit(certificate)}
              onDelete={() => onDelete(certificate.id)}
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
  <div class="mt-4 rounded-lg bg-white shadow-md dark:bg-black">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-neutral-900"
          ><tr
            ><th
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Nama Sertifikat</th
            ><th
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >No. Sertifikat</th
            ><th
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Barang Sertifikat</th
            ><th
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Status</th
            ><th
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Terbit</th
            ><th
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Expired</th
            ><th
              class="w-28 px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Aksi</th
            ></tr
          ></thead
        >
        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-black">
          {#each certificates as certificate (certificate.id)}
            <tr
              ><td class="px-3 py-4 text-sm font-medium whitespace-nowrap"
                ><button
                  type="button"
                  class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                  onclick={() => onOpenDetail(certificate)}>{certificate.name}</button
                ></td
              ><td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300"
                >{certificate.no_certificate}</td
              ><td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300"
                >{certificate.barang_certificate?.name || '-'}</td
              ><td class="px-3 py-4 text-sm whitespace-nowrap"
                ><StatusBadge status={certificate.status} type="certificate" /></td
              ><td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300"
                >{certificate.date_of_issue ? formatDate(certificate.date_of_issue) : '-'}</td
              ><td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300"
                >{certificate.date_of_expired ? formatDate(certificate.date_of_expired) : '-'}</td
              ><td class="px-3 py-4 text-sm whitespace-nowrap"
                ><RowActions
                  itemName={certificate.name}
                  {canUpdate}
                  {canDelete}
                  onDetail={() => onOpenDetail(certificate)}
                  onEdit={() => onEdit(certificate)}
                  onDelete={() => onDelete(certificate.id)}
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
