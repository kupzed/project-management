<script lang="ts">
  import { onMount } from 'svelte';
  import Drawer from '$lib/components/Drawer.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import ActivityDetail from '$lib/components/detail/ActivityDetail.svelte';
  import ActivityFormModal from '$lib/components/form/ActivityFormModal.svelte';
  import EmptyState from '$lib/components/common/EmptyState.svelte';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import DateFilterDropdown from '$lib/components/ui/DateFilterDropdown.svelte';
  import ViewToggle from '$lib/components/ui/ViewToggle.svelte';
  import { DEFAULT_PER_PAGE, PER_PAGE_OPTIONS } from '$lib/constants';
  import {
    createActivity,
    deleteActivity,
    fetchActivities as fetchActivityList,
    updateActivity
  } from '$lib/services/activityService';
  import { userPermissions } from '$lib/stores/permissions';
  import { extractApiErrors } from '$lib/utils/errors';
  import { lockBodyScroll } from '$lib/utils/scroll-lock';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type {
    Activity,
    ActivityEditForm,
    ActivityFilterParams,
    ActivityJenis,
    ActivityKategori,
    SortOrder
  } from '$lib/types';
  import ActivityFilterBar from './ActivityFilterBar.svelte';
  import ActivityListView from './ActivityListView.svelte';
  import ActivityTableView from './ActivityTableView.svelte';
  import {
    makeActivityForm,
    toNamedOptions,
    withProjectMitra,
    type ActivityModalEditForm,
    type ActivityProjectOption,
    type NamedOption,
    type View
  } from './activity-page';

  let activities = $state<Activity[]>([]);
  let projects = $state<ActivityProjectOption[]>([]);
  let vendors = $state<NamedOption[]>([]);
  let loading = $state(true);
  let error = $state('');
  let search = $state('');
  let jenisFilter = $state<ActivityJenis | ''>('');
  let kategoriFilter = $state<ActivityKategori | ''>('');
  let dateFromFilter = $state('');
  let dateToFilter = $state('');
  let currentPage = $state(1);
  let lastPage = $state(1);
  let totalActivities = $state(0);
  let perPage = $state(DEFAULT_PER_PAGE);
  let activeView = $state<View>('table');
  let sortBy = $state<'created' | 'activity_date'>('activity_date');
  let sortDir = $state<SortOrder>('asc');
  let showCreateModal = $state(false);
  let showEditModal = $state(false);
  let editingActivity = $state<Activity | null>(null);
  let showDetailDrawer = $state(false);
  let selectedActivity = $state<Activity | null>(null);
  let form = $state<ActivityModalEditForm>(makeActivityForm());
  let activityKategoriList = $state<ActivityKategori[]>([]);
  let activityJenisList = $state<ActivityJenis[]>([]);
  let previousJenis = $state('');

  let canCreateActivity = $derived(($userPermissions ?? []).includes('activity-create'));
  let canUpdateActivity = $derived(($userPermissions ?? []).includes('activity-update'));
  let canDeleteActivity = $derived(($userPermissions ?? []).includes('activity-delete'));

  function getParams(): ActivityFilterParams {
    return {
      search: search || undefined,
      jenis: jenisFilter || undefined,
      kategori: kategoriFilter || undefined,
      date_from: dateFromFilter || undefined,
      date_to: dateToFilter || undefined,
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
      const payload = await fetchActivityList(getParams());
      const vendorOptions = toNamedOptions(payload.formDeps.vendors);
      const customerOptions = toNamedOptions(payload.formDeps.customers);
      activities = payload.data;
      currentPage = payload.meta.current_page;
      lastPage = payload.meta.last_page;
      totalActivities = payload.meta.total;
      vendors = vendorOptions;
      projects = withProjectMitra(payload.formDeps.projects, vendorOptions, customerOptions);
      activityKategoriList = payload.formDeps.kategoriList;
      activityJenisList = payload.formDeps.jenisList;
    } catch (err: unknown) {
      error = extractApiErrors(err);
      showError(error);
    } finally {
      loading = false;
    }
  }

  function refetchFromFirstPage(): void {
    currentPage = 1;
    void loadActivities();
  }

  function clearFilters(): void {
    search = '';
    jenisFilter = '';
    kategoriFilter = '';
    dateFromFilter = '';
    dateToFilter = '';
    sortBy = 'activity_date';
    sortDir = 'asc';
    currentPage = 1;
    void loadActivities();
  }

  function goToPage(page: number): void {
    currentPage = page;
    void loadActivities();
  }

  function changePerPage(nextPerPage: number): void {
    perPage = nextPerPage;
    currentPage = 1;
    void loadActivities();
  }

  function projectMitraId(projectId: number | string | ''): number | undefined {
    const selectedProject = projects.find((project) => project.id === Number(projectId));
    return selectedProject?.mitra_id ?? undefined;
  }

  function openCreateModal(): void {
    if (!canCreateActivity) return;
    form = makeActivityForm();
    showCreateModal = true;
  }

  function openEditModal(activity: Activity): void {
    if (!canUpdateActivity) return;
    editingActivity = activity;
    form = makeActivityForm(activity);
    showEditModal = true;
  }

  function openDetailDrawer(activity: Activity): void {
    selectedActivity = activity;
    showDetailDrawer = true;
  }

  async function handleSubmitCreate(): Promise<void> {
    if (!canCreateActivity) return;
    try {
      await createActivity(form, projectMitraId(form.project_id));
      showSuccess('Aktivitas berhasil ditambahkan!');
      showCreateModal = false;
      await loadActivities();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  async function handleSubmitUpdate(): Promise<void> {
    if (!canUpdateActivity || !editingActivity) return;
    try {
      await updateActivity(
        editingActivity.id,
        form as ActivityEditForm,
        projectMitraId(form.project_id)
      );
      showSuccess('Aktivitas berhasil diperbarui!');
      showEditModal = false;
      editingActivity = null;
      await loadActivities();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDelete(activityId: number): Promise<void> {
    if (!canDeleteActivity) return;
    const confirmed = await confirm({
      title: 'Hapus aktivitas?',
      text: 'Aktivitas yang dihapus tidak dapat dikembalikan.',
      confirmText: 'Hapus',
      isDangerous: true
    });
    if (!confirmed) return;

    try {
      await deleteActivity(activityId);
      showSuccess('Aktivitas berhasil dihapus!');
      await loadActivities();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  function syncMitraFromJenis(): void {
    if (form.jenis === 'Customer' && form.project_id) {
      const nextMitraId = projectMitraId(form.project_id);
      if (nextMitraId && form.mitra_id !== nextMitraId) form.mitra_id = nextMitraId;
    } else if (form.jenis === 'Internal' && form.mitra_id !== '1') {
      form.mitra_id = '1';
    } else if (
      form.jenis === 'Vendor' &&
      !vendors.some((vendor) => vendor.id === Number(form.mitra_id))
    ) {
      form.mitra_id = '';
    } else if (!form.jenis) {
      form.mitra_id = '';
    }
  }

  $effect(() => {
    const modalOpen = showCreateModal || showEditModal;
    const currentJenis = form.jenis;
    const currentProjectId = form.project_id;

    if (!modalOpen) {
      previousJenis = '';
      return;
    }

    if (currentJenis !== previousJenis) {
      previousJenis = currentJenis;
      syncMitraFromJenis();
      return;
    }

    if (currentJenis === 'Customer' && currentProjectId) {
      syncMitraFromJenis();
    }
  });

  $effect(() => {
    lockBodyScroll(showDetailDrawer || showCreateModal || showEditModal);
    return () => lockBodyScroll(false);
  });

  onMount(() => {
    void loadActivities();
  });
</script>

<svelte:head>
  <title>Daftar Activity - Indogreen</title>
</svelte:head>

<ActivityFilterBar
  bind:search
  bind:jenisFilter
  bind:kategoriFilter
  {activityJenisList}
  {activityKategoriList}
  canCreate={canCreateActivity}
  onFilter={refetchFromFirstPage}
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
    sortByField="activity_date"
    sortByCreatedLabel="Urutkan Berdasarkan Create"
    sortByDateLabel="Urutkan Tanggal Aktivitas"
    onFilter={refetchFromFirstPage}
    onClear={clearFilters}
  />
</div>

{#snippet activityPagination()}
  <Pagination
    {currentPage}
    {lastPage}
    totalItems={totalActivities}
    itemsPerPage={perPage}
    perPageOptions={[...PER_PAGE_OPTIONS]}
    onPageChange={goToPage}
    onPerPageChange={changePerPage}
  />
{/snippet}

{#if loading}
  <LoadingState label="Memuat aktivitas..." />
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if activities.length === 0}
  <div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
    <EmptyState title="Belum ada aktivitas." />
  </div>
{:else if activeView === 'list'}
  <ActivityListView
    {activities}
    canUpdate={canUpdateActivity}
    canDelete={canDeleteActivity}
    onOpenDetail={openDetailDrawer}
    onEdit={openEditModal}
    onDelete={handleDelete}
    footer={activityPagination}
  />
{:else}
  <ActivityTableView
    {activities}
    canUpdate={canUpdateActivity}
    canDelete={canDeleteActivity}
    onOpenDetail={openDetailDrawer}
    onEdit={openEditModal}
    onDelete={handleDelete}
    footer={activityPagination}
  />
{/if}

<ActivityFormModal
  bind:show={showCreateModal}
  bind:form
  title="Form Aktivitas Baru"
  submitLabel="Tambah Aktivitas"
  idPrefix="create"
  {projects}
  {vendors}
  {activityKategoriList}
  {activityJenisList}
  allowRemoveAttachment={false}
  onSubmit={handleSubmitCreate}
/>

{#if editingActivity}
  <ActivityFormModal
    bind:show={showEditModal}
    bind:form
    title="Edit Aktivitas"
    submitLabel="Update Aktivitas"
    idPrefix="edit"
    {projects}
    {vendors}
    {activityKategoriList}
    {activityJenisList}
    allowRemoveAttachment={true}
    onSubmit={handleSubmitUpdate}
  />
{/if}

<Drawer
  bind:show={showDetailDrawer}
  title="Detail Activity"
  onClose={() => (showDetailDrawer = false)}
>
  <ActivityDetail activity={selectedActivity} />
</Drawer>
