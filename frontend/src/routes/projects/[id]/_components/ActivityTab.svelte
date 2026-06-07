<script lang="ts">
  import Drawer from '$lib/components/Drawer.svelte';
  import ActivityDetail from '$lib/components/detail/ActivityDetail.svelte';
  import ActivityFormModal from '$lib/components/form/ActivityFormModal.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import DateFilterDropdown from '$lib/components/ui/DateFilterDropdown.svelte';
  import SearchInput from '$lib/components/ui/SearchInput.svelte';
  import ViewToggle, { type ViewToggleView } from '$lib/components/ui/ViewToggle.svelte';
  import { DEFAULT_PER_PAGE, PER_PAGE_OPTIONS } from '$lib/constants';
  import {
    createActivity,
    deleteActivity,
    fetchActivities as fetchActivityList,
    updateActivity
  } from '$lib/services/activityService';
  import { userPermissions } from '$lib/stores/permissions';
  import { extractApiErrors } from '$lib/utils/errors';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type {
    Activity,
    ActivityFilterParams,
    ActivityJenis,
    ActivityKategori,
    Project,
    SortOrder
  } from '$lib/types';
  import ActivityTabResults from './ActivityTabResults.svelte';
  import {
    makeCreateActivityForm,
    makeEditActivityForm,
    toNamedOptions,
    type ActivityModalEditForm,
    type ActivityModalForm,
    type NamedOption
  } from './activity-tab';

  /**
   * Activity tab props. The tab owns activity filters, pagination, drawers, and CRUD state.
   */
  let { project }: { project: Project } = $props();

  let activities = $state<Activity[]>([]);
  let loading = $state(true);
  let error = $state('');
  let view = $state<ViewToggleView>('table');
  let jenisFilter = $state<ActivityJenis | ''>('');
  let kategoriFilter = $state<ActivityKategori | ''>('');
  let vendorFilter = $state('');
  let searchInput = $state('');
  let search = $state('');
  let dateFrom = $state('');
  let dateTo = $state('');
  let sortBy = $state<'created' | 'activity_date'>('activity_date');
  let sortDir = $state<SortOrder>('asc');
  let currentPage = $state(1);
  let perPage = $state(DEFAULT_PER_PAGE);
  let lastPage = $state(1);
  let totalItems = $state(0);
  let vendors = $state<NamedOption[]>([]);
  let vendorOptions = $state<NamedOption[]>([]);
  let kategoriList = $state<ActivityKategori[]>([]);
  let jenisList = $state<ActivityJenis[]>([]);
  let showCreateModal = $state(false);
  let showEditModal = $state(false);
  let showDetailDrawer = $state(false);
  let selectedActivity = $state<Activity | null>(null);
  let editingActivity = $state<Activity | null>(null);
  function initialCreateForm(): ActivityModalForm {
    return makeCreateActivityForm(project);
  }
  function initialEditForm(): ActivityModalEditForm {
    return makeEditActivityForm(project);
  }
  let createForm = $state<ActivityModalForm>(initialCreateForm());
  let editForm = $state<ActivityModalEditForm>(initialEditForm());
  let pageOptions = $derived([...PER_PAGE_OPTIONS]);
  let canCreate = $derived(($userPermissions ?? []).includes('activity-create'));
  let canUpdate = $derived(($userPermissions ?? []).includes('activity-update'));
  let canDelete = $derived(($userPermissions ?? []).includes('activity-delete'));

  function buildParams(): ActivityFilterParams {
    return {
      project_id: project.id,
      jenis: jenisFilter,
      kategori: kategoriFilter,
      mitra_id: jenisFilter === 'Vendor' && vendorFilter ? vendorFilter : undefined,
      search,
      date_from: dateFrom,
      date_to: dateTo,
      page: currentPage,
      per_page: perPage,
      sort_by: sortBy,
      sort_dir: sortDir
    };
  }

  async function loadActivities(): Promise<void> {
    loading = true;
    error = '';
    try {
      const response = await fetchActivityList(buildParams());
      activities = response.data;
      currentPage = response.meta.current_page;
      lastPage = response.meta.last_page;
      perPage = response.meta.per_page;
      totalItems = response.meta.total;
      vendorOptions = toNamedOptions(response.vendorOptions);
      vendors = toNamedOptions(response.formDeps.vendors);
      kategoriList = response.formDeps.kategoriList;
      jenisList = response.formDeps.jenisList;
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
    jenisFilter = '';
    kategoriFilter = '';
    vendorFilter = '';
    searchInput = '';
    search = '';
    dateFrom = '';
    dateTo = '';
    sortBy = 'activity_date';
    sortDir = 'asc';
    currentPage = 1;
  }

  function openCreateActivityModal(): void {
    createForm = makeCreateActivityForm(project);
    showCreateModal = true;
  }

  function openEditActivityModal(activity: Activity): void {
    editingActivity = activity;
    editForm = makeEditActivityForm(project, activity);
    showEditModal = true;
  }

  function openActivityDetailDrawer(activity: Activity): void {
    selectedActivity = activity;
    showDetailDrawer = true;
  }

  async function handleCreateActivity(): Promise<void> {
    try {
      await createActivity(createForm, project.mitra_id ?? undefined);
      showCreateModal = false;
      showSuccess('Aktivitas berhasil ditambahkan!');
      await loadActivities();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  async function handleUpdateActivity(): Promise<void> {
    if (!editingActivity) return;
    try {
      await updateActivity(editingActivity.id, editForm, project.mitra_id ?? undefined);
      showEditModal = false;
      editingActivity = null;
      showSuccess('Aktivitas berhasil diperbarui!');
      await loadActivities();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDeleteActivity(id: number): Promise<void> {
    const accepted = await confirm({
      title: 'Hapus aktivitas?',
      text: 'Apakah Anda yakin ingin menghapus aktivitas ini?',
      confirmText: 'Hapus',
      isDangerous: true
    });

    if (!accepted) return;
    try {
      await deleteActivity(id);
      showSuccess('Aktivitas berhasil dihapus!');
      await loadActivities();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  function handlePerPageChange(nextPerPage: number): void {
    perPage = nextPerPage;
    currentPage = 1;
  }

  $effect(() => {
    if (jenisFilter !== 'Vendor') {
      vendorFilter = '';
    }
  });

  $effect(() => {
    void loadActivities();
  });
</script>

<div class="mb-8 min-w-0">
  <div class="mb-4 flex min-w-0 flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex w-full min-w-0 flex-col gap-2 sm:flex-row lg:w-auto">
      <select
        bind:value={jenisFilter}
        onchange={resetToFirstPage}
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      >
        <option value="">Jenis: Semua</option>
        {#each jenisList as jenis (jenis)}<option value={jenis}>{jenis}</option>{/each}
      </select>
      {#if jenisFilter === 'Vendor'}
        <select
          bind:value={vendorFilter}
          onchange={resetToFirstPage}
          class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        >
          <option value="">Vendor: Semua</option>
          {#each vendorOptions as vendor (vendor.id)}<option value={vendor.id}>{vendor.nama}</option
            >{/each}
        </select>
      {/if}
      <select
        bind:value={kategoriFilter}
        onchange={resetToFirstPage}
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      >
        <option value="">Kategori: Semua</option>
        {#each kategoriList as kategori (kategori)}<option value={kategori}>{kategori}</option
          >{/each}
      </select>
    </div>
    <div class="w-full min-w-0 flex-1">
      <SearchInput
        bind:value={searchInput}
        placeholder="Cari aktivitas..."
        onSearch={(value) => {
          search = value;
          currentPage = 1;
        }}
      />
    </div>
    {#if canCreate}
      <button
        type="button"
        onclick={openCreateActivityModal}
        class="w-full rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none sm:w-auto lg:shrink-0 dark:focus:ring-offset-gray-800"
      >
        Tambah Aktivitas
      </button>
    {/if}
  </div>

  <div class="mb-4 flex min-w-0 items-center justify-between gap-3">
    <ViewToggle bind:activeView={view} />
    <DateFilterDropdown
      title="Filter Tanggal"
      bind:dateFrom
      bind:dateTo
      bind:sortBy
      bind:sortDir
      sortByField="activity_date"
      sortByCreatedLabel="Urutkan Berdasarkan Create"
      sortByDateLabel="Urutkan Tanggal Aktivitas"
      idPrefix="activity-date-filter"
      onFilter={resetToFirstPage}
      onClear={clearFilters}
    />
  </div>

  <ActivityTabResults
    {loading}
    {error}
    {activities}
    {view}
    {currentPage}
    {lastPage}
    {totalItems}
    {perPage}
    {pageOptions}
    {canUpdate}
    {canDelete}
    onOpenDetail={openActivityDetailDrawer}
    onEdit={openEditActivityModal}
    onDelete={handleDeleteActivity}
    onPageChange={(page) => (currentPage = page)}
    onPerPageChange={handlePerPageChange}
  />
</div>

<ActivityFormModal
  bind:show={showCreateModal}
  bind:form={createForm}
  title="Form Tambah Aktivitas"
  submitLabel="Tambah Aktivitas"
  idPrefix="create_activity"
  projects={[project]}
  showProjectSelect={false}
  {vendors}
  activityKategoriList={kategoriList}
  activityJenisList={jenisList}
  allowRemoveAttachment={false}
  onSubmit={handleCreateActivity}
/>
{#if editingActivity}<ActivityFormModal
    bind:show={showEditModal}
    bind:form={editForm}
    title="Edit Aktivitas"
    submitLabel="Update Aktivitas"
    idPrefix="edit_activity"
    projects={[project]}
    showProjectSelect={false}
    {vendors}
    activityKategoriList={kategoriList}
    activityJenisList={jenisList}
    allowRemoveAttachment={true}
    onSubmit={handleUpdateActivity}
  />{/if}
<Drawer
  bind:show={showDetailDrawer}
  title="Detail Activity"
  onClose={() => (showDetailDrawer = false)}
>
  <ActivityDetail activity={selectedActivity} />
</Drawer>
