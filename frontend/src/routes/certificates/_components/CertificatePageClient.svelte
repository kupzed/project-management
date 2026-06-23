<script lang="ts">
  import { onMount } from 'svelte';
  import Drawer from '$lib/components/Drawer.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import EmptyState from '$lib/components/common/EmptyState.svelte';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import CertificateDetail from '$lib/components/detail/CertificatesDetail.svelte';
  import CertificateFormModal from '$lib/components/form/CertificateFormModal.svelte';
  import DateFilterDropdown from '$lib/components/ui/DateFilterDropdown.svelte';
  import ViewToggle from '$lib/components/ui/ViewToggle.svelte';
  import { DEFAULT_PER_PAGE, PER_PAGE_OPTIONS } from '$lib/constants';
  import {
    createCertificate,
    deleteCertificate,
    fetchCertificates,
    updateCertificate
  } from '$lib/services/certificateService';
  import { userPermissions } from '$lib/stores/permissions';
  import { extractApiErrors } from '$lib/utils/errors';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type {
    Certificate,
    CertificateEditForm,
    CertificateFilterParams,
    CertificateForm,
    CertificateSortBy,
    CertificateStatus,
    ExistingAttachment,
    Option,
    ProjectSummary,
    SortOrder
  } from '$lib/types';
  import CertificateFilterBar from './CertificateFilterBar.svelte';
  import CertificateListView from './CertificateListView.svelte';
  import CertificateTableView from './CertificateTableView.svelte';

  type View = 'table' | 'list';
  type CertificateModalForm = Omit<CertificateEditForm, 'attachment_descriptions'> & {
    attachment_descriptions: string[];
  };

  function makeForm(certificate?: Certificate): CertificateModalForm {
    return {
      name: certificate?.name ?? '',
      no_certificate: certificate?.no_certificate ?? '',
      project_id: certificate?.project_id ?? '',
      barang_certificate_id: certificate?.barang_certificate_id ?? '',
      status: certificate?.status ?? '',
      date_of_issue: certificate?.date_of_issue
        ? new Date(certificate.date_of_issue).toISOString().split('T')[0]
        : '',
      date_of_expired: certificate?.date_of_expired
        ? new Date(certificate.date_of_expired).toISOString().split('T')[0]
        : '',
      attachments: [],
      attachment_names: [],
      attachment_descriptions: [],
      existing_attachments: normalizeExistingAttachments(certificate?.attachments ?? []),
      removed_existing_ids: []
    };
  }

  function normalizeExistingAttachments(
    attachments: Certificate['attachments']
  ): ExistingAttachment[] {
    return (attachments ?? []).flatMap((attachment) => {
      if (typeof attachment.id !== 'number') return [];
      return [
        {
          id: attachment.id,
          name: attachment.name ?? 'Lampiran',
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

  let items = $state<Certificate[]>([]);
  let projects = $state<ProjectSummary[]>([]);
  let filteredBarangCertificates = $state<Option[]>([]);
  let statuses = $state<CertificateStatus[]>([]);
  let loading = $state(true);
  let error = $state('');
  let search = $state('');
  let statusFilter = $state<CertificateStatus | ''>('');
  let dateFromFilter = $state('');
  let dateToFilter = $state('');
  let currentPage = $state(1);
  let lastPage = $state(1);
  let totalItems = $state(0);
  let perPage = $state(DEFAULT_PER_PAGE);
  let activeView = $state<View>('table');
  let sortBy = $state<CertificateSortBy>('created');
  let sortDir = $state<SortOrder>('desc');
  let showCreateModal = $state(false);
  let showEditModal = $state(false);
  let showDetailDrawer = $state(false);
  let editingItem = $state<Certificate | null>(null);
  let selectedItem = $state<Certificate | null>(null);
  let form = $state<CertificateModalForm>(makeForm());

  let canCreateCertificate = $derived(($userPermissions ?? []).includes('certificate-create'));
  let canUpdateCertificate = $derived(($userPermissions ?? []).includes('certificate-update'));
  let canDeleteCertificate = $derived(($userPermissions ?? []).includes('certificate-delete'));

  function getParams(): CertificateFilterParams {
    return {
      search: search || undefined,
      status: statusFilter,
      date_from: dateFromFilter || undefined,
      date_to: dateToFilter || undefined,
      page: currentPage,
      per_page: perPage,
      sort_by: sortBy,
      sort_dir: sortDir
    };
  }

  async function fetchList(): Promise<void> {
    loading = true;
    error = '';
    try {
      const result = await fetchCertificates(getParams());
      items = result.data;
      projects = result.formDeps.projects;
      statuses = result.formDeps.statuses;
      filteredBarangCertificates = result.formDeps.barang_options;
      currentPage = result.meta.current_page;
      lastPage = result.meta.last_page;
      totalItems = result.meta.total;
    } catch (err) {
      error = extractApiErrors(err);
    } finally {
      loading = false;
    }
  }

  async function fetchBarangCertificatesByProject(projectId: number): Promise<void> {
    if (!projectId) {
      filteredBarangCertificates = [];
      return;
    }

    try {
      const result = await fetchCertificates({ project_id: projectId, per_page: 5 });
      filteredBarangCertificates = result.formDeps.barang_options;
    } catch (err) {
      console.error('Failed to fetch barang certificates by project', err);
      filteredBarangCertificates = [];
    }
  }

  function handleProjectChange(projectId: number | '' | null): void {
    form.barang_certificate_id = '';
    if (projectId) {
      void fetchBarangCertificatesByProject(Number(projectId));
      return;
    }

    filteredBarangCertificates = [];
  }

  function handleFilterOrSearch(): void {
    currentPage = 1;
    void fetchList();
  }

  function handleClearDateFilter(): void {
    currentPage = 1;
    void fetchList();
  }

  function goToPage(page: number): void {
    if (page > 0 && page <= lastPage) {
      currentPage = page;
      void fetchList();
    }
  }

  function changePerPage(nextPerPage: number): void {
    perPage = nextPerPage;
    currentPage = 1;
    void fetchList();
  }

  function openCreateModal(): void {
    if (!canCreateCertificate) {
      return;
    }

    form = makeForm();
    filteredBarangCertificates = [];
    showCreateModal = true;
  }

  function openEditModal(item: Certificate): void {
    if (!canUpdateCertificate) {
      return;
    }

    editingItem = item;
    form = makeForm(item);
    if (item.project_id) {
      void fetchBarangCertificatesByProject(item.project_id);
    } else {
      filteredBarangCertificates = [];
    }
    showEditModal = true;
  }

  function openDetailDrawer(item: Certificate): void {
    selectedItem = item;
    showDetailDrawer = true;
  }

  async function handleSubmitCreate(): Promise<void> {
    if (!canCreateCertificate) {
      return;
    }

    try {
      await createCertificate(form as CertificateForm);
      showCreateModal = false;
      showSuccess('Data berhasil ditambahkan');
      await fetchList();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function handleSubmitUpdate(): Promise<void> {
    if (!canUpdateCertificate) {
      return;
    }
    if (!editingItem?.id) return;

    try {
      await updateCertificate(editingItem.id, form);
      showEditModal = false;
      showSuccess('Data berhasil diperbarui');
      await fetchList();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDelete(id: number): Promise<void> {
    if (!canDeleteCertificate) {
      return;
    }

    const confirmed = await confirm({
      title: 'Hapus sertifikat?',
      text: 'Data sertifikat akan dihapus permanen.',
      confirmText: 'Hapus',
      isDangerous: true
    });
    if (!confirmed) return;

    try {
      await deleteCertificate(id);
      showSuccess('Data berhasil dihapus');
      await fetchList();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  onMount(() => {
    void fetchList();
  });
</script>

<svelte:head>
  <title>Daftar Sertifikat - Indogreen</title>
</svelte:head>

<CertificateFilterBar
  bind:search
  bind:statusFilter
  {statuses}
  canCreate={canCreateCertificate}
  onFilter={handleFilterOrSearch}
  onCreate={openCreateModal}
/>

<div class="mb-4 flex items-center justify-between">
  <ViewToggle bind:activeView />
  <DateFilterDropdown
    title="Filter Tanggal"
    bind:dateFrom={dateFromFilter}
    bind:dateTo={dateToFilter}
    bind:sortBy
    bind:sortDir
    sortByField="date_of_issue"
    sortByCreatedLabel="Urutkan Berdasarkan Create"
    sortByDateLabel="Urutkan Tanggal Terbit"
    fromLabel="Dari Tanggal Terbit"
    toLabel="Sampai Tanggal Terbit"
    idPrefix="certificate-date-filter"
    onFilter={handleFilterOrSearch}
    onClear={handleClearDateFilter}
  />
</div>

{#snippet certificatePagination()}
  <Pagination
    {currentPage}
    {lastPage}
    {totalItems}
    itemsPerPage={perPage}
    perPageOptions={[...PER_PAGE_OPTIONS]}
    onPageChange={goToPage}
    onPerPageChange={changePerPage}
  />
{/snippet}

{#if loading}
  <LoadingState label="Memuat sertifikat..." />
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if items.length === 0}
  <EmptyState title="Belum ada data." />
{:else if activeView === 'list'}
  <CertificateListView
    {items}
    canUpdate={canUpdateCertificate}
    canDelete={canDeleteCertificate}
    onOpenDetail={openDetailDrawer}
    onEdit={openEditModal}
    onDelete={handleDelete}
    footer={certificatePagination}
  />
{:else}
  <CertificateTableView
    {items}
    canUpdate={canUpdateCertificate}
    canDelete={canDeleteCertificate}
    onOpenDetail={openDetailDrawer}
    onEdit={openEditModal}
    onDelete={handleDelete}
    footer={certificatePagination}
  />
{/if}

<CertificateFormModal
  bind:show={showCreateModal}
  title="Tambah Sertifikat"
  submitLabel="Simpan"
  idPrefix="create"
  bind:form
  {projects}
  barangOptions={filteredBarangCertificates}
  {statuses}
  {handleProjectChange}
  onSubmit={handleSubmitCreate}
/>

{#if editingItem}
  <CertificateFormModal
    bind:show={showEditModal}
    title="Edit Sertifikat"
    submitLabel="Update"
    idPrefix="edit"
    bind:form
    {projects}
    barangOptions={filteredBarangCertificates}
    {statuses}
    {handleProjectChange}
    onSubmit={handleSubmitUpdate}
  />
{/if}

<Drawer
  bind:show={showDetailDrawer}
  title="Detail Sertifikat"
  onClose={() => (showDetailDrawer = false)}
>
  <CertificateDetail certificates={selectedItem} />
</Drawer>
