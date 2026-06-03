<script lang="ts">
  import Drawer from '$lib/components/Drawer.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import ActivityDetail from '$lib/components/detail/ActivityDetail.svelte';
  import ActivityFormModal from '$lib/components/form/ActivityFormModal.svelte';
  import EmptyState from '$lib/components/common/EmptyState.svelte';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
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
  import { formatDate } from '$lib/utils/formatters';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type {
    Activity,
    ActivityFilterParams,
    ActivityForm,
    ActivityJenis,
    ActivityKategori,
    ExistingAttachment,
    Option,
    Project,
    SortOrder
  } from '$lib/types';
  import RowActions from './RowActions.svelte';

  type NamedOption = { id: number; nama: string };
  type ProjectWithMitraFlag = Project & {
    mitra?: (Project['mitra'] & { is_customer?: boolean }) | null;
  };
  type ActivityModalForm = Omit<
    ActivityForm,
    'attachment_descriptions' | 'short_desc' | 'from' | 'to' | 'value'
  > & {
    short_desc: string;
    from: string;
    to: string;
    value: number | string;
    mitra_id: number | string | '' | null;
    attachment_descriptions: string[];
  };
  type ActivityModalEditForm = ActivityModalForm & {
    existing_attachments: ExistingAttachment[];
    removed_existing_ids: number[];
  };

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
  let createForm = $state<ActivityModalForm>(makeCreateForm());
  let editForm = $state<ActivityModalEditForm>(makeEditForm());
  let pageOptions = $derived([...PER_PAGE_OPTIONS]);
  let canCreate = $derived(($userPermissions ?? []).includes('activity-create'));
  let canUpdate = $derived(($userPermissions ?? []).includes('activity-update'));
  let canDelete = $derived(($userPermissions ?? []).includes('activity-delete'));

  function optionName(option: Option): string {
    return option.nama ?? option.name ?? option.title ?? option.no_seri ?? String(option.id);
  }

  function toNamedOptions(options: Option[]): NamedOption[] {
    return options.map((option) => ({ id: option.id, nama: optionName(option) }));
  }

  function projectUsesCustomer(projectValue: Project): boolean {
    const projectWithFlag = projectValue as ProjectWithMitraFlag;
    return Boolean(projectWithFlag.mitra?.is_customer ?? projectValue.mitra_id);
  }

  function makeCreateForm(): ActivityModalForm {
    return {
      name: '',
      short_desc: '',
      description: '',
      project_id: project.id,
      kategori: '',
      value: 0,
      activity_date: '',
      jenis: projectUsesCustomer(project) ? 'Customer' : '',
      mitra_id: projectUsesCustomer(project) ? project.mitra_id : '',
      from: '',
      to: '',
      attachments: [],
      attachment_names: [],
      attachment_descriptions: []
    };
  }

  function makeEditForm(activity?: Activity): ActivityModalEditForm {
    return {
      ...makeCreateForm(),
      name: activity?.name ?? '',
      short_desc: activity?.short_desc ?? '',
      description: activity?.description ?? '',
      kategori: activity?.kategori ?? '',
      value: activity?.value ?? 0,
      activity_date: activity?.activity_date ?? '',
      jenis: activity?.jenis ?? (projectUsesCustomer(project) ? 'Customer' : ''),
      mitra_id: activity?.mitra_id ?? '',
      from: activity?.from ?? '',
      to: activity?.to ?? '',
      existing_attachments: normalizeExistingAttachments(activity?.attachments ?? []),
      removed_existing_ids: []
    };
  }

  function normalizeExistingAttachments(
    attachments: Activity['attachments']
  ): ExistingAttachment[] {
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
    createForm = makeCreateForm();
    showCreateModal = true;
  }

  function openEditActivityModal(activity: Activity): void {
    editingActivity = activity;
    editForm = makeEditForm(activity);
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
        {#each jenisList as jenis}<option value={jenis}>{jenis}</option>{/each}
      </select>
      {#if jenisFilter === 'Vendor'}
        <select
          bind:value={vendorFilter}
          onchange={resetToFirstPage}
          class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        >
          <option value="">Vendor: Semua</option>
          {#each vendorOptions as vendor}<option value={vendor.id}>{vendor.nama}</option>{/each}
        </select>
      {/if}
      <select
        bind:value={kategoriFilter}
        onchange={resetToFirstPage}
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      >
        <option value="">Kategori: Semua</option>
        {#each kategoriList as kategori}<option value={kategori}>{kategori}</option>{/each}
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
            <!-- svelte-ignore a11y_no_static_element_interactions -->
            <button
              type="button"
              class="block w-full cursor-pointer px-4 py-4 text-left hover:bg-gray-50 sm:px-6 dark:hover:bg-neutral-950"
              onclick={() => openActivityDetailDrawer(activity)}
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
                onDetail={() => openActivityDetailDrawer(activity)}
                onEdit={() => openEditActivityModal(activity)}
                onDelete={() => handleDeleteActivity(activity.id)}
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
        onPageChange={(page) => (currentPage = page)}
        onPerPageChange={handlePerPageChange}
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
                    onclick={() => openActivityDetailDrawer(activity)}>{activity.name}</button
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
                    onDetail={() => openActivityDetailDrawer(activity)}
                    onEdit={() => openEditActivityModal(activity)}
                    onDelete={() => handleDeleteActivity(activity.id)}
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
        onPageChange={(page) => (currentPage = page)}
        onPerPageChange={handlePerPageChange}
      />
    </div>
  {/if}
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
