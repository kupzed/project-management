<script lang="ts">
  import { onMount } from 'svelte';
  import Drawer from '$lib/components/Drawer.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import ActivityDetail from '$lib/components/detail/ActivityDetail.svelte';
  import ProjectDetail from '$lib/components/detail/ProjectDetail.svelte';
  import ProjectFormModal from '$lib/components/form/ProjectFormModal.svelte';
  import EmptyState from '$lib/components/common/EmptyState.svelte';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import DateFilterDropdown from '$lib/components/ui/DateFilterDropdown.svelte';
  import ViewToggle from '$lib/components/ui/ViewToggle.svelte';
  import { DEFAULT_PER_PAGE, PER_PAGE_OPTIONS } from '$lib/constants';
  import {
    createProject,
    deleteProject,
    fetchProjects as fetchProjectList,
    updateProject
  } from '$lib/services/projectService';
  import { userPermissions } from '$lib/stores/permissions';
  import { extractApiErrors } from '$lib/utils/errors';
  import { lockBodyScroll } from '$lib/utils/scroll-lock';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type {
    Activity,
    MitraSummary,
    Project,
    ProjectFilterParams,
    ProjectForm,
    ProjectKategori,
    ProjectSortBy,
    ProjectStatus,
    SortOrder
  } from '$lib/types';
  import ProjectFilterBar from './ProjectFilterBar.svelte';
  import ProjectListView from './ProjectListView.svelte';
  import ProjectTableView from './ProjectTableView.svelte';

  type View = 'table' | 'list';

  function defaultProjectForm(): ProjectForm {
    return {
      name: '',
      description: '',
      status: '',
      start_date: '',
      finish_date: '',
      mitra_id: '',
      kategori: '',
      lokasi: '',
      no_po: '',
      no_so: '',
      is_cert_projects: false
    };
  }

  function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
  }

  function isMitraSummaryArray(value: unknown): value is MitraSummary[] {
    return (
      Array.isArray(value) &&
      value.every((item) => {
        if (!isRecord(item)) return false;
        return typeof item.id === 'number' && typeof item.nama === 'string';
      })
    );
  }

  function toStringList<T extends string>(value: unknown): T[] {
    return Array.isArray(value) ? value.filter((item): item is T => typeof item === 'string') : [];
  }

  let projects = $state<Project[]>([]);
  let customers = $state<MitraSummary[]>([]);
  let projectStatuses = $state<ProjectStatus[]>([]);
  let projectKategoris = $state<ProjectKategori[]>([]);
  let loading = $state(true);
  let error = $state('');
  let search = $state('');
  let statusFilter = $state<ProjectStatus | ''>('');
  let kategoriFilter = $state<ProjectKategori | ''>('');
  let certProjectFilter = $state(false);
  let dateFromFilter = $state('');
  let dateToFilter = $state('');
  let sortBy = $state<ProjectSortBy>('created');
  let sortDir = $state<SortOrder>('desc');
  let currentPage = $state(1);
  let lastPage = $state(1);
  let totalProjects = $state(0);
  let perPage = $state(DEFAULT_PER_PAGE);
  let activeView = $state<View>('table');
  let showCreateModal = $state(false);
  let showEditModal = $state(false);
  let editingProject = $state<Project | null>(null);
  let showDetailDrawer = $state(false);
  let selectedProject = $state<Project | null>(null);
  let showActivityDetailDrawer = $state(false);
  let selectedActivity = $state<Activity | null>(null);
  let form = $state<ProjectForm>(defaultProjectForm());
  let openActivities = $state<Record<number, boolean>>({});

  let canCreate = $derived(($userPermissions ?? []).includes('project-create'));
  let canUpdate = $derived(($userPermissions ?? []).includes('project-update'));
  let canDelete = $derived(($userPermissions ?? []).includes('project-delete'));
  let canViewActivity = $derived(($userPermissions ?? []).includes('activity-view'));

  function updateFormDependencies(deps?: Record<string, unknown>): void {
    if (!deps) return;
    if (isMitraSummaryArray(deps.customers)) customers = deps.customers;
    projectStatuses = toStringList<ProjectStatus>(deps.project_status_list);
    projectKategoris = toStringList<ProjectKategori>(deps.project_kategori_list);
  }

  function getParams(): ProjectFilterParams {
    return {
      search: search || undefined,
      status: statusFilter || undefined,
      kategori: kategoriFilter || undefined,
      is_cert_projects: certProjectFilter ? 1 : undefined,
      date_from: dateFromFilter || undefined,
      date_to: dateToFilter || undefined,
      page: currentPage,
      per_page: perPage,
      sort_by: sortBy,
      sort_dir: sortDir
    };
  }

  async function loadProjects(): Promise<void> {
    loading = true;
    error = '';
    try {
      const payload = await fetchProjectList(getParams());
      projects = payload.data;
      currentPage = payload.meta.current_page;
      lastPage = payload.meta.last_page;
      totalProjects = payload.meta.total;
      updateFormDependencies(payload.form_dependencies);
    } catch (err: unknown) {
      error = extractApiErrors(err);
      showError(error);
    } finally {
      loading = false;
    }
  }

  function refetchFromFirstPage(): void {
    currentPage = 1;
    void loadProjects();
  }

  function clearFilters(): void {
    search = '';
    statusFilter = '';
    kategoriFilter = '';
    certProjectFilter = false;
    dateFromFilter = '';
    dateToFilter = '';
    sortBy = 'created';
    sortDir = 'desc';
    currentPage = 1;
    void loadProjects();
  }

  function goToPage(page: number): void {
    currentPage = page;
    void loadProjects();
  }

  function changePerPage(nextPerPage: number): void {
    perPage = nextPerPage;
    currentPage = 1;
    void loadProjects();
  }

  function openCreateModal(): void {
    if (!canCreate) return;
    form = defaultProjectForm();
    showCreateModal = true;
  }

  function openEditModal(project: Project): void {
    if (!canUpdate) return;
    editingProject = project;
    form = {
      name: project.name,
      description: project.description,
      status: project.status,
      start_date: project.start_date,
      finish_date: project.finish_date ?? '',
      mitra_id: project.mitra_id ?? '',
      kategori: project.kategori,
      lokasi: project.lokasi ?? '',
      no_po: project.no_po ?? '',
      no_so: project.no_so ?? '',
      is_cert_projects: project.is_cert_projects
    };
    showEditModal = true;
  }

  function openDetailDrawer(project: Project): void {
    selectedProject = project;
    showDetailDrawer = true;
  }

  function openActivityDetail(activity: Activity, project: Project): void {
    selectedActivity = {
      ...activity,
      project: activity.project ?? {
        id: project.id,
        name: project.name,
        mitra_id: project.mitra_id
      }
    };
    showActivityDetailDrawer = true;
  }

  function toggleActivities(projectId: number): void {
    openActivities = { [projectId]: !openActivities[projectId] };
  }

  async function handleSubmitCreate(): Promise<void> {
    if (!canCreate) return;
    try {
      await createProject(form);
      showSuccess('Project berhasil ditambahkan!');
      showCreateModal = false;
      await loadProjects();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  async function handleSubmitUpdate(): Promise<void> {
    if (!canUpdate || !editingProject) return;
    try {
      await updateProject(editingProject.id, form);
      showSuccess('Project berhasil diperbarui!');
      showEditModal = false;
      editingProject = null;
      await loadProjects();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDelete(projectId: number): Promise<void> {
    if (!canDelete) return;
    const confirmed = await confirm({
      title: 'Hapus project?',
      text: 'Project yang dihapus tidak dapat dikembalikan.',
      confirmText: 'Hapus',
      isDangerous: true
    });
    if (!confirmed) return;

    try {
      await deleteProject(projectId);
      showSuccess('Project berhasil dihapus!');
      await loadProjects();
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  $effect(() => {
    lockBodyScroll(
      showDetailDrawer || showActivityDetailDrawer || showCreateModal || showEditModal
    );
    return () => lockBodyScroll(false);
  });

  onMount(() => {
    void loadProjects();
  });
</script>

<svelte:head>
  <title>Daftar Project - Indogreen</title>
</svelte:head>

<ProjectFilterBar
  bind:search
  bind:statusFilter
  bind:kategoriFilter
  bind:certProjectFilter
  {projectStatuses}
  {projectKategoris}
  {canCreate}
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
    sortByField="start_date"
    sortByCreatedLabel="Urutkan Berdasarkan Create"
    sortByDateLabel="Urutkan Tanggal Dilaksanakan"
    onFilter={refetchFromFirstPage}
    onClear={clearFilters}
  />
</div>

{#snippet projectPagination()}
  <Pagination
    {currentPage}
    {lastPage}
    totalItems={totalProjects}
    itemsPerPage={perPage}
    perPageOptions={[...PER_PAGE_OPTIONS]}
    onPageChange={goToPage}
    onPerPageChange={changePerPage}
  />
{/snippet}

{#if loading}
  <LoadingState label="Memuat project..." />
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if projects.length === 0}
  <div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
    <EmptyState title="Belum ada project." />
  </div>
{:else if activeView === 'list'}
  <ProjectListView
    {projects}
    {openActivities}
    {canViewActivity}
    {canUpdate}
    {canDelete}
    onToggleActivities={toggleActivities}
    onOpenDetail={openDetailDrawer}
    onOpenActivityDetail={openActivityDetail}
    onEdit={openEditModal}
    onDelete={handleDelete}
    footer={projectPagination}
  />
{:else}
  <ProjectTableView
    {projects}
    {openActivities}
    {canViewActivity}
    {canUpdate}
    {canDelete}
    onToggleActivities={toggleActivities}
    onOpenDetail={openDetailDrawer}
    onOpenActivityDetail={openActivityDetail}
    onEdit={openEditModal}
    onDelete={handleDelete}
    footer={projectPagination}
  />
{/if}

<ProjectFormModal
  bind:show={showCreateModal}
  bind:form
  title="Form Project Baru"
  submitLabel="Tambah Project"
  idPrefix="create"
  {customers}
  {projectStatuses}
  {projectKategoris}
  onSubmit={handleSubmitCreate}
/>

{#if editingProject}
  <ProjectFormModal
    bind:show={showEditModal}
    bind:form
    title="Edit Project"
    submitLabel="Update Project"
    idPrefix="edit"
    {customers}
    {projectStatuses}
    {projectKategoris}
    onSubmit={handleSubmitUpdate}
  />
{/if}

<Drawer
  bind:show={showDetailDrawer}
  title="Detail Project"
  onClose={() => (showDetailDrawer = false)}
>
  <ProjectDetail project={selectedProject} />
</Drawer>

<Drawer
  bind:show={showActivityDetailDrawer}
  title="Detail Kegiatan"
  onClose={() => (showActivityDetailDrawer = false)}
>
  <ActivityDetail activity={selectedActivity} />
</Drawer>
