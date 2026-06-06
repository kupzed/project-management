<script lang="ts">
  import { onMount } from 'svelte';
  import Drawer from '$lib/components/Drawer.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import EmptyState from '$lib/components/common/EmptyState.svelte';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import MitraDetail from '$lib/components/detail/MitraDetail.svelte';
  import MitraFormModal from '$lib/components/form/MitraFormModal.svelte';
  import DateFilterDropdown from '$lib/components/ui/DateFilterDropdown.svelte';
  import ViewToggle from '$lib/components/ui/ViewToggle.svelte';
  import { DEFAULT_PER_PAGE, PER_PAGE_OPTIONS } from '$lib/constants';
  import { createMitra, deleteMitra, fetchMitras, updateMitra } from '$lib/services/mitraService';
  import { userPermissions } from '$lib/stores/permissions';
  import { extractApiErrors } from '$lib/utils/errors';
  import { lockBodyScroll } from '$lib/utils/scroll-lock';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type {
    Mitra,
    MitraCategory,
    MitraFilterParams,
    MitraForm,
    MitraKategoriOption,
    SortOrder
  } from '$lib/types';
  import MitraFilterBar from './MitraFilterBar.svelte';
  import MitraListView from './MitraListView.svelte';
  import MitraTableView from './MitraTableView.svelte';

  type View = 'table' | 'list';
  type MitraModalForm = Required<
    Omit<
      MitraForm,
      | 'website'
      | 'email'
      | 'kontak_1'
      | 'kontak_1_nama'
      | 'kontak_1_jabatan'
      | 'kontak_2'
      | 'kontak_2_nama'
      | 'kontak_2_jabatan'
    >
  > & {
    website: string;
    email: string;
    kontak_1: string;
    kontak_1_nama: string;
    kontak_1_jabatan: string;
    kontak_2: string;
    kontak_2_nama: string;
    kontak_2_jabatan: string;
  };

  const DEFAULT_KATEGORI_OPTIONS: MitraKategoriOption[] = [
    { value: 'pribadi', label: 'Pribadi' },
    { value: 'perusahaan', label: 'Perusahaan' },
    { value: 'customer', label: 'Customer' },
    { value: 'vendor', label: 'Vendor' }
  ];

  function makeForm(mitra?: Mitra): MitraModalForm {
    return {
      nama: mitra?.nama ?? '',
      is_pribadi: mitra?.is_pribadi ?? false,
      is_perusahaan: mitra?.is_perusahaan ?? false,
      is_customer: mitra?.is_customer ?? false,
      is_vendor: mitra?.is_vendor ?? false,
      alamat: mitra?.alamat ?? '',
      website: mitra?.website ?? '',
      email: mitra?.email ?? '',
      kontak_1: mitra?.kontak_1 ?? '',
      kontak_1_nama: mitra?.kontak_1_nama ?? '',
      kontak_1_jabatan: mitra?.kontak_1_jabatan ?? '',
      kontak_2: mitra?.kontak_2 ?? '',
      kontak_2_nama: mitra?.kontak_2_nama ?? '',
      kontak_2_jabatan: mitra?.kontak_2_jabatan ?? ''
    };
  }

  let mitras = $state<Mitra[]>([]);
  let loading = $state(true);
  let error = $state('');
  let search = $state('');
  let kategoriFilter = $state<MitraCategory | ''>('');
  let dateFromFilter = $state('');
  let dateToFilter = $state('');
  let sortBy = $state<'created'>('created');
  let sortDir = $state<SortOrder>('desc');
  let currentPage = $state(1);
  let lastPage = $state(1);
  let totalMitras = $state(0);
  let perPage = $state(DEFAULT_PER_PAGE);
  let activeView = $state<View>('table');
  let showCreateModal = $state(false);
  let showEditModal = $state(false);
  let showDetailDrawer = $state(false);
  let editingMitra = $state<Mitra | null>(null);
  let selectedMitra = $state<Mitra | null>(null);
  let form = $state<MitraModalForm>(makeForm());
  let kategoriOptions = $state<MitraKategoriOption[]>(DEFAULT_KATEGORI_OPTIONS);

  let canCreateMitra = $derived(($userPermissions ?? []).includes('mitra-create'));
  let canUpdateMitra = $derived(($userPermissions ?? []).includes('mitra-update'));
  let canDeleteMitra = $derived(($userPermissions ?? []).includes('mitra-delete'));

  function getParams(): MitraFilterParams {
    return {
      search: search || undefined,
      kategori: kategoriFilter,
      date_from: dateFromFilter || undefined,
      date_to: dateToFilter || undefined,
      sort_by: sortBy,
      sort_dir: sortDir,
      page: currentPage,
      per_page: perPage
    };
  }

  async function fetchList(): Promise<void> {
    loading = true;
    error = '';
    try {
      const result = await fetchMitras(getParams());
      mitras = result.data;
      currentPage = result.meta.current_page;
      lastPage = result.meta.last_page;
      totalMitras = result.meta.total;
      kategoriOptions = result.formDeps.kategori_options;
    } catch (err) {
      error = extractApiErrors(err);
    } finally {
      loading = false;
    }
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
    if (!canCreateMitra) {
      return;
    }

    form = makeForm();
    showCreateModal = true;
  }

  function openEditModal(mitra: Mitra): void {
    if (!canUpdateMitra) {
      return;
    }

    editingMitra = mitra;
    form = makeForm(mitra);
    showEditModal = true;
  }

  function openDetailDrawer(mitra: Mitra): void {
    selectedMitra = mitra;
    showDetailDrawer = true;
  }

  async function handleSubmitCreate(): Promise<void> {
    if (!canCreateMitra) {
      return;
    }

    try {
      await createMitra(form);
      showCreateModal = false;
      showSuccess('Mitra berhasil ditambahkan!');
      await fetchList();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function handleSubmitUpdate(): Promise<void> {
    if (!editingMitra?.id || !canUpdateMitra) {
      return;
    }

    try {
      await updateMitra(editingMitra.id, form);
      showEditModal = false;
      showSuccess('Mitra berhasil diperbarui!');
      await fetchList();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDelete(mitraId: number): Promise<void> {
    if (!canDeleteMitra) {
      return;
    }

    const accepted = await confirm({
      title: 'Hapus mitra?',
      text: 'Apakah Anda yakin ingin menghapus mitra ini?',
      confirmText: 'Hapus',
      isDangerous: true
    });
    if (!accepted) return;

    try {
      await deleteMitra(mitraId);
      showSuccess('Mitra berhasil dihapus!');
      await fetchList();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  $effect(() => {
    lockBodyScroll(showDetailDrawer || showCreateModal || showEditModal);
    return () => lockBodyScroll(false);
  });

  onMount(() => {
    void fetchList();
  });
</script>

<svelte:head>
  <title>Daftar Mitra - Indogreen</title>
</svelte:head>

<MitraFilterBar
  bind:search
  bind:kategoriFilter
  {kategoriOptions}
  canCreate={canCreateMitra}
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
    sortByField="created"
    sortByCreatedLabel="Urutkan Berdasarkan Create"
    sortByDateLabel="Urutkan Tanggal Dibuat"
    fromLabel="Dari Tanggal Dibuat"
    toLabel="Sampai Tanggal Dibuat"
    idPrefix="mitra-date-filter"
    onFilter={handleFilterOrSearch}
    onClear={handleClearDateFilter}
  />
</div>

{#snippet mitraPagination()}
  <Pagination
    {currentPage}
    {lastPage}
    totalItems={totalMitras}
    itemsPerPage={perPage}
    perPageOptions={[...PER_PAGE_OPTIONS]}
    onPageChange={goToPage}
    onPerPageChange={changePerPage}
  />
{/snippet}

{#if loading}
  <LoadingState label="Memuat mitra..." />
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if mitras.length === 0}
  <EmptyState title="Belum ada mitra." />
{:else if activeView === 'list'}
  <MitraListView
    {mitras}
    canUpdate={canUpdateMitra}
    canDelete={canDeleteMitra}
    onOpenDetail={openDetailDrawer}
    onEdit={openEditModal}
    onDelete={handleDelete}
    footer={mitraPagination}
  />
{:else}
  <MitraTableView
    {mitras}
    canUpdate={canUpdateMitra}
    canDelete={canDeleteMitra}
    onOpenDetail={openDetailDrawer}
    onEdit={openEditModal}
    onDelete={handleDelete}
    footer={mitraPagination}
  />
{/if}

<MitraFormModal
  bind:show={showCreateModal}
  title="Tambah Mitra"
  submitLabel="Tambah Mitra"
  idPrefix="create"
  bind:form
  onSubmit={handleSubmitCreate}
/>

{#if editingMitra}
  <MitraFormModal
    bind:show={showEditModal}
    title="Edit Mitra"
    submitLabel="Update Mitra"
    idPrefix="edit"
    bind:form
    onSubmit={handleSubmitUpdate}
  />
{/if}

<Drawer
  bind:show={showDetailDrawer}
  title="Detail Mitra"
  onClose={() => (showDetailDrawer = false)}
>
  <MitraDetail mitra={selectedMitra} />
</Drawer>
