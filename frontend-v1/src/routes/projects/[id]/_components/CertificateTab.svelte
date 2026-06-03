<script lang="ts">
  import Drawer from '$lib/components/Drawer.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import CertificatesDetail from '$lib/components/detail/CertificatesDetail.svelte';
  import CertificateFormModal from '$lib/components/form/CertificateFormModal.svelte';
  import EmptyState from '$lib/components/common/EmptyState.svelte';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import DateFilterDropdown from '$lib/components/ui/DateFilterDropdown.svelte';
  import SearchInput from '$lib/components/ui/SearchInput.svelte';
  import StatusBadge from '$lib/components/ui/StatusBadge.svelte';
  import ViewToggle, { type ViewToggleView } from '$lib/components/ui/ViewToggle.svelte';
  import { CERTIFICATE_STATUS_OPTIONS, DEFAULT_PER_PAGE, PER_PAGE_OPTIONS } from '$lib/constants';
  import {
    createCertificate,
    deleteCertificate,
    fetchCertificates as fetchCertificateList,
    updateCertificate
  } from '$lib/services/certificateService';
  import { userPermissions } from '$lib/stores/permissions';
  import { extractApiErrors } from '$lib/utils/errors';
  import { formatDate } from '$lib/utils/formatters';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type {
    Certificate,
    CertificateFilterParams,
    CertificateForm,
    CertificateSortBy,
    CertificateStatus,
    ExistingAttachment,
    Option,
    Project,
    SortOrder
  } from '$lib/types';

  type CertificateModalForm = Omit<CertificateForm, 'attachment_descriptions'> & {
    attachment_descriptions: string[];
    existing_attachments?: ExistingAttachment[];
    removed_existing_ids?: number[];
  };

  /**
   * Certificate tab props. The tab owns certificate filters, pagination, drawers, and CRUD state.
   */
  let { project }: { project: Project } = $props();

  let certificates = $state<Certificate[]>([]);
  let loading = $state(true);
  let error = $state('');
  let view = $state<ViewToggleView>('table');
  let statusFilter = $state<CertificateStatus | ''>('');
  let searchInput = $state('');
  let search = $state('');
  let dateFrom = $state('');
  let dateTo = $state('');
  let dateSortField = $state<Extract<CertificateSortBy, 'date_of_issue' | 'date_of_expired'>>('date_of_issue');
  let sortBy = $state<CertificateSortBy>('created');
  let sortDir = $state<SortOrder>('desc');
  let currentPage = $state(1);
  let perPage = $state(DEFAULT_PER_PAGE);
  let lastPage = $state(1);
  let totalItems = $state(0);
  let statuses = $state<CertificateStatus[]>([...CERTIFICATE_STATUS_OPTIONS]);
  let barangOptions = $state<Option[]>([]);
  let showCreateModal = $state(false);
  let showEditModal = $state(false);
  let showDetailDrawer = $state(false);
  let selectedCertificate = $state<Certificate | null>(null);
  let editingCertificate = $state<Certificate | null>(null);
  let certificateForm = $state<CertificateModalForm>(makeCreateForm());
  let pageOptions = $derived([...PER_PAGE_OPTIONS]);
  let canCreate = $derived(($userPermissions ?? []).includes('certificate-create'));
  let canUpdate = $derived(($userPermissions ?? []).includes('certificate-update'));
  let canDelete = $derived(($userPermissions ?? []).includes('certificate-delete'));

  function makeCreateForm(): CertificateModalForm {
    return {
      name: '',
      no_certificate: '',
      project_id: project.id,
      barang_certificate_id: '',
      status: '',
      date_of_issue: '',
      date_of_expired: '',
      attachments: [],
      attachment_names: [],
      attachment_descriptions: []
    };
  }

  function makeEditForm(certificate: Certificate): CertificateModalForm {
    return {
      ...makeCreateForm(),
      name: certificate.name,
      no_certificate: certificate.no_certificate,
      barang_certificate_id: certificate.barang_certificate_id ?? '',
      status: certificate.status,
      date_of_issue: certificate.date_of_issue ?? '',
      date_of_expired: certificate.date_of_expired ?? '',
      existing_attachments: normalizeExistingAttachments(certificate.attachments ?? []),
      removed_existing_ids: []
    };
  }

  function normalizeExistingAttachments(attachments: Certificate['attachments']): ExistingAttachment[] {
    return (attachments ?? []).flatMap((attachment) => {
      if (typeof attachment.id !== 'number') {
        return [];
      }

      return [
        {
          id: attachment.id,
          name: attachment.name,
          description: attachment.description ?? null,
          size: attachment.size ?? null,
          sizeLabel: attachment.sizeLabel ?? null,
          path: attachment.path,
          url: attachment.url,
          original_name: attachment.name
        }
      ];
    });
  }

  function buildParams(): CertificateFilterParams {
    return {
      project_id: project.id,
      search,
      status: statusFilter,
      date_from: dateFrom,
      date_to: dateTo,
      page: currentPage,
      per_page: perPage,
      sort_by: sortBy,
      sort_dir: sortDir
    };
  }

  async function loadCertificates(): Promise<void> {
    loading = true;
    error = '';
    try {
      const response = await fetchCertificateList(buildParams());
      certificates = response.data;
      currentPage = response.meta.current_page;
      lastPage = response.meta.last_page;
      perPage = response.meta.per_page;
      totalItems = response.meta.total;
      barangOptions = response.formDeps.barang_options;
      statuses = response.formDeps.statuses.length ? response.formDeps.statuses : statuses;
    } catch (err: unknown) {
      error = extractApiErrors(err);
    } finally {
      loading = false;
    }
  }

  function resetToFirstPage(): void {
    currentPage = 1;
  }

  function clearFilters(): void {
    statusFilter = '';
    searchInput = '';
    search = '';
    dateFrom = '';
    dateTo = '';
    sortBy = 'created';
    sortDir = 'desc';
    currentPage = 1;
  }

  function openCreateCertificateModal(): void {
    certificateForm = makeCreateForm();
    showCreateModal = true;
  }

  function openEditCertificateModal(certificate: Certificate): void {
    editingCertificate = certificate;
    certificateForm = makeEditForm(certificate);
    showEditModal = true;
  }

  function openCertificateDetailDrawer(certificate: Certificate): void {
    selectedCertificate = certificate;
    showDetailDrawer = true;
  }

  async function handleCreateCertificate(): Promise<void> {
    try {
      await createCertificate(certificateForm, project.id);
      showCreateModal = false;
      showSuccess('Certificate berhasil ditambahkan');
      await loadCertificates();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  async function handleUpdateCertificate(): Promise<void> {
    if (!editingCertificate) return;
    try {
      await updateCertificate(editingCertificate.id, certificateForm, project.id);
      showEditModal = false;
      editingCertificate = null;
      showSuccess('Certificate berhasil diperbarui');
      await loadCertificates();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDeleteCertificate(id: number): Promise<void> {
    const accepted = await confirm({
      title: 'Hapus certificate?',
      text: 'Yakin ingin menghapus certificate ini?',
      confirmText: 'Hapus',
      isDangerous: true
    });

    if (!accepted) return;
    try {
      await deleteCertificate(id);
      showSuccess('Certificate berhasil dihapus');
      await loadCertificates();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  function handlePerPageChange(nextPerPage: number): void {
    perPage = nextPerPage;
    currentPage = 1;
  }

  $effect(() => {
    void loadCertificates();
  });
</script>

<div class="mb-8">
  <div class="mb-4 flex flex-col items-center justify-between space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4">
    <select bind:value={statusFilter} onchange={resetToFirstPage} class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100">
      <option value="">Status: Semua</option>
      {#each statuses as status}<option value={status}>{status}</option>{/each}
    </select>
    <div class="w-full flex-grow sm:w-auto">
      <SearchInput bind:value={searchInput} placeholder="Cari sertifikat..." onSearch={(value) => { search = value; currentPage = 1; }} />
    </div>
    {#if canCreate}
      <button type="button" onclick={openCreateCertificateModal} class="w-full rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none sm:w-auto dark:focus:ring-offset-gray-800">
        Tambah Sertif
      </button>
    {/if}
  </div>

  <div class="mb-4 flex items-center justify-between gap-3">
    <ViewToggle bind:activeView={view} />
    <div class="flex items-center gap-2">
      <select bind:value={dateSortField} class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100" title="Pilih tanggal yang diurutkan">
        <option value="date_of_issue">Tanggal Terbit</option>
        <option value="date_of_expired">Tanggal Expired</option>
      </select>
      <DateFilterDropdown title="Filter Tanggal" bind:dateFrom bind:dateTo bind:sortBy bind:sortDir sortByField={dateSortField} sortByCreatedLabel="Urutkan Berdasarkan Create" sortByDateLabel="Urutkan Tanggal" fromLabel="Dari Tanggal Terbit" toLabel="Sampai Tanggal Terbit" idPrefix="certificate-date-filter" onFilter={resetToFirstPage} onClear={clearFilters} />
    </div>
  </div>

  {#if loading}
    <LoadingState label="Memuat sertifikat..." />
  {:else if error}
    <p class="text-red-500">{error}</p>
  {:else if certificates.length === 0}
    <div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-black"><EmptyState title="Belum ada sertifikat untuk proyek ini." /></div>
  {:else if view === 'list'}
    <div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
      <ul class="divide-y divide-gray-200 dark:divide-gray-700">
        {#each certificates as item (item.id)}
          <li>
            <button type="button" class="block w-full cursor-pointer px-4 py-4 text-left hover:bg-gray-50 sm:px-6 dark:hover:bg-neutral-950" onclick={() => openCertificateDetailDrawer(item)}>
              <div class="flex items-center justify-between"><p class="truncate text-sm font-medium text-indigo-600 dark:text-indigo-400">{item.name}</p><StatusBadge status={item.status} type="certificate" /></div>
              <div class="mt-2 sm:flex sm:justify-between"><p class="text-sm text-gray-500 dark:text-gray-300">Barang: {item.barang_certificate?.name || '-'} | No: {item.no_certificate}</p><p class="mt-2 text-sm text-gray-500 sm:mt-0 dark:text-gray-300">Terbit: {item.date_of_issue ? formatDate(item.date_of_issue, 'long') : '-'}</p></div>
            </button>
            <div class="flex justify-end space-x-2 px-4 py-2 sm:px-6">
              <button type="button" onclick={() => openCertificateDetailDrawer(item)} class="inline-flex items-center rounded-md border border-transparent bg-yellow-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-yellow-700">Detail</button>
              {#if canUpdate}<button type="button" onclick={() => openEditCertificateModal(item)} class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-indigo-700">Edit</button>{/if}
              {#if canDelete}<button type="button" onclick={() => handleDeleteCertificate(item.id)} class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700">Hapus</button>{/if}
            </div>
          </li>
        {/each}
      </ul>
      <Pagination {currentPage} {lastPage} totalItems={totalItems} itemsPerPage={perPage} perPageOptions={pageOptions} onPageChange={(page) => (currentPage = page)} onPerPageChange={handlePerPageChange} />
    </div>
  {:else}
    <div class="mt-4 rounded-lg bg-white shadow-md dark:bg-black">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-neutral-900"><tr><th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Nama Sertifikat</th><th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">No. Sertifikat</th><th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Barang Sertifikat</th><th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th><th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Terbit</th><th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Expired</th><th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Aksi</th></tr></thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-black">
            {#each certificates as item (item.id)}
              <tr><td class="whitespace-nowrap px-3 py-4 text-sm font-medium"><button type="button" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" onclick={() => openCertificateDetailDrawer(item)}>{item.name}</button></td><td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{item.no_certificate}</td><td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{item.barang_certificate?.name || '-'}</td><td class="whitespace-nowrap px-3 py-4 text-sm"><StatusBadge status={item.status} type="certificate" /></td><td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{item.date_of_issue ? formatDate(item.date_of_issue) : '-'}</td><td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{item.date_of_expired ? formatDate(item.date_of_expired) : '-'}</td><td class="whitespace-nowrap px-3 py-4 text-sm"><div class="flex items-center space-x-2"><button type="button" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" onclick={() => openCertificateDetailDrawer(item)}>Detail</button>{#if canUpdate}<button type="button" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" onclick={() => openEditCertificateModal(item)}>Edit</button>{/if}{#if canDelete}<button type="button" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" onclick={() => handleDeleteCertificate(item.id)}>Hapus</button>{/if}</div></td></tr>
            {/each}
          </tbody>
        </table>
      </div>
      <Pagination {currentPage} {lastPage} totalItems={totalItems} itemsPerPage={perPage} perPageOptions={pageOptions} onPageChange={(page) => (currentPage = page)} onPerPageChange={handlePerPageChange} />
    </div>
  {/if}
</div>

<CertificateFormModal bind:show={showCreateModal} bind:form={certificateForm} title="Tambah Sertifikat" submitLabel="Simpan" idPrefix="create_cert" projects={[]} {barangOptions} {statuses} allowRemoveAttachment={true} showProjectSelect={false} onSubmit={handleCreateCertificate} />
{#if editingCertificate}<CertificateFormModal bind:show={showEditModal} bind:form={certificateForm} title="Edit Sertifikat" submitLabel="Update" idPrefix="edit_cert" projects={[]} {barangOptions} {statuses} allowRemoveAttachment={true} showProjectSelect={false} onSubmit={handleUpdateCertificate} />{/if}
<Drawer bind:show={showDetailDrawer} title="Detail Sertifikat" onClose={() => (showDetailDrawer = false)}>
  <CertificatesDetail certificates={selectedCertificate} />
</Drawer>
