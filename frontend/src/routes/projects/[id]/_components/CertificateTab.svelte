<script lang="ts">
  import Drawer from '$lib/components/Drawer.svelte';
  import CertificatesDetail from '$lib/components/detail/CertificatesDetail.svelte';
  import CertificateFormModal from '$lib/components/form/CertificateFormModal.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import DateFilterDropdown from '$lib/components/ui/DateFilterDropdown.svelte';
  import SearchInput from '$lib/components/ui/SearchInput.svelte';
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
  import { showError, showSuccess } from '$lib/utils/toast';
  import type {
    Certificate,
    CertificateFilterParams,
    CertificateSortBy,
    CertificateStatus,
    Option,
    Project,
    SortOrder
  } from '$lib/types';
  import CertificateTabResults from './CertificateTabResults.svelte';
  import {
    makeCreateCertificateForm,
    makeEditCertificateForm,
    type CertificateModalForm
  } from './certificate-tab';

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
  let dateSortField =
    $state<Extract<CertificateSortBy, 'date_of_issue' | 'date_of_expired'>>('date_of_issue');
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
  function initialCertificateForm(): CertificateModalForm {
    return makeCreateCertificateForm(project.id);
  }
  let certificateForm = $state<CertificateModalForm>(initialCertificateForm());
  let pageOptions = $derived([...PER_PAGE_OPTIONS]);
  let canCreate = $derived(($userPermissions ?? []).includes('certificate-create'));
  let canUpdate = $derived(($userPermissions ?? []).includes('certificate-update'));
  let canDelete = $derived(($userPermissions ?? []).includes('certificate-delete'));

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
    certificateForm = makeCreateCertificateForm(project.id);
    showCreateModal = true;
  }

  function openEditCertificateModal(certificate: Certificate): void {
    editingCertificate = certificate;
    certificateForm = makeEditCertificateForm(project.id, certificate);
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
  <div
    class="mb-4 flex flex-col items-center justify-between space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4"
  >
    <select
      bind:value={statusFilter}
      onchange={resetToFirstPage}
      class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Status: Semua</option>
      {#each statuses as status (status)}<option value={status}>{status}</option>{/each}
    </select>
    <div class="w-full flex-grow sm:w-auto">
      <SearchInput
        bind:value={searchInput}
        placeholder="Cari sertifikat..."
        onSearch={(value) => {
          search = value;
          currentPage = 1;
        }}
      />
    </div>
    {#if canCreate}
      <button
        type="button"
        onclick={openCreateCertificateModal}
        class="w-full rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none sm:w-auto dark:focus:ring-offset-gray-800"
      >
        Tambah Sertif
      </button>
    {/if}
  </div>

  <div class="mb-4 flex items-center justify-between gap-3">
    <ViewToggle bind:activeView={view} />
    <div class="flex items-center gap-2">
      <select
        bind:value={dateSortField}
        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        title="Pilih tanggal yang diurutkan"
      >
        <option value="date_of_issue">Tanggal Terbit</option>
        <option value="date_of_expired">Tanggal Expired</option>
      </select>
      <DateFilterDropdown
        title="Filter Tanggal"
        bind:dateFrom
        bind:dateTo
        bind:sortBy
        bind:sortDir
        sortByField={dateSortField}
        sortByCreatedLabel="Urutkan Berdasarkan Create"
        sortByDateLabel="Urutkan Tanggal"
        fromLabel="Dari Tanggal Terbit"
        toLabel="Sampai Tanggal Terbit"
        idPrefix="certificate-date-filter"
        onFilter={resetToFirstPage}
        onClear={clearFilters}
      />
    </div>
  </div>

  <CertificateTabResults
    {loading}
    {error}
    {certificates}
    {view}
    {currentPage}
    {lastPage}
    {totalItems}
    {perPage}
    {pageOptions}
    {canUpdate}
    {canDelete}
    onOpenDetail={openCertificateDetailDrawer}
    onEdit={openEditCertificateModal}
    onDelete={handleDeleteCertificate}
    onPageChange={(page) => (currentPage = page)}
    onPerPageChange={handlePerPageChange}
  />
</div>

<CertificateFormModal
  bind:show={showCreateModal}
  bind:form={certificateForm}
  title="Tambah Sertifikat"
  submitLabel="Simpan"
  idPrefix="create_cert"
  projects={[]}
  {barangOptions}
  {statuses}
  allowRemoveAttachment={true}
  showProjectSelect={false}
  onSubmit={handleCreateCertificate}
/>
{#if editingCertificate}<CertificateFormModal
    bind:show={showEditModal}
    bind:form={certificateForm}
    title="Edit Sertifikat"
    submitLabel="Update"
    idPrefix="edit_cert"
    projects={[]}
    {barangOptions}
    {statuses}
    allowRemoveAttachment={true}
    showProjectSelect={false}
    onSubmit={handleUpdateCertificate}
  />{/if}
<Drawer
  bind:show={showDetailDrawer}
  title="Detail Sertifikat"
  onClose={() => (showDetailDrawer = false)}
>
  <CertificatesDetail certificates={selectedCertificate} />
</Drawer>
