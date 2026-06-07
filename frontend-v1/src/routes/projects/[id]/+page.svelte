<script lang="ts">
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import ProjectDetail from '$lib/components/detail/ProjectDetail.svelte';
  import ProjectFormModal from '$lib/components/form/ProjectFormModal.svelte';
  import { deleteProject, fetchProject, updateProject } from '$lib/services/projectService';
  import { userPermissions } from '$lib/stores/permissions';
  import type {
    MitraSummary,
    Project,
    ProjectForm,
    ProjectKategori,
    ProjectStatus
  } from '$lib/types';
  import { PROJECT_KATEGORI_OPTIONS, PROJECT_STATUS_OPTIONS } from '$lib/constants';
  import { extractApiErrors } from '$lib/utils/errors';
  import { lockBodyScroll } from '$lib/utils/scroll-lock';
  import { showError, showSuccess } from '$lib/utils/toast';
  import ActivityTab from './_components/ActivityTab.svelte';
  import CertificateTab from './_components/CertificateTab.svelte';
  import ProjectHeader from './_components/ProjectHeader.svelte';
  import ProjectTabs, { type ProjectDetailTab } from './_components/ProjectTabs.svelte';

  let project = $state<Project | null>(null);
  let loading = $state(true);
  let error = $state('');
  let activeTab = $state<ProjectDetailTab>('activity');
  let hasVisitedActivity = $state(true);
  let hasVisitedCertificates = $state(false);
  let showEditModal = $state(false);
  let editProjectForm = $state<ProjectForm>(makeProjectForm());
  let customers = $state<MitraSummary[]>([]);
  let projectStatuses = $state<ProjectStatus[]>([...PROJECT_STATUS_OPTIONS]);
  let projectKategoris = $state<ProjectKategori[]>([...PROJECT_KATEGORI_OPTIONS]);
  let canUpdateProject = $derived(($userPermissions ?? []).includes('project-update'));
  let canDeleteProject = $derived(($userPermissions ?? []).includes('project-delete'));

  function makeProjectForm(projectValue?: Project): ProjectForm {
    return {
      name: projectValue?.name ?? '',
      description: projectValue?.description ?? '',
      status: projectValue?.status ?? '',
      start_date: projectValue?.start_date ?? '',
      finish_date: projectValue?.finish_date ?? '',
      mitra_id: projectValue?.mitra_id ?? '',
      kategori: projectValue?.kategori ?? '',
      lokasi: projectValue?.lokasi ?? '',
      no_po: projectValue?.no_po ?? '',
      no_so: projectValue?.no_so ?? '',
      is_cert_projects: projectValue?.is_cert_projects ?? false
    };
  }

  function applyFormDependencies(formDeps: Record<string, unknown>): void {
    if (Array.isArray(formDeps.customers)) customers = formDeps.customers as MitraSummary[];
    if (Array.isArray(formDeps.project_status_list)) {
      projectStatuses = formDeps.project_status_list as ProjectStatus[];
    }
    if (Array.isArray(formDeps.project_kategori_list)) {
      projectKategoris = formDeps.project_kategori_list as ProjectKategori[];
    }
  }

  async function loadProject(projectId: string): Promise<void> {
    loading = true;
    error = '';

    try {
      const response = await fetchProject(projectId);
      project = response.project;
      editProjectForm = makeProjectForm(response.project);
      applyFormDependencies(response.formDeps);
      hasVisitedActivity = activeTab === 'activity';
      hasVisitedCertificates = activeTab === 'certificates' && response.project.is_cert_projects;
    } catch (err: unknown) {
      error = extractApiErrors(err);
    } finally {
      loading = false;
    }
  }

  function openEditProjectModal(): void {
    if (!project || !canUpdateProject) {
      return;
    }

    editProjectForm = makeProjectForm(project);
    showEditModal = true;
  }

  async function handleSubmitUpdateProject(): Promise<void> {
    if (!project || !canUpdateProject) {
      return;
    }

    try {
      const updatedProject = await updateProject(project.id, editProjectForm);
      project = updatedProject;
      editProjectForm = makeProjectForm(updatedProject);
      showEditModal = false;
      showSuccess('Proyek berhasil diperbarui!');
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDeleteProject(): Promise<void> {
    if (!project || !canDeleteProject) {
      return;
    }

    const accepted = await confirm({
      title: 'Hapus proyek?',
      text: 'Apakah Anda yakin ingin menghapus proyek ini?',
      confirmText: 'Hapus',
      isDangerous: true
    });

    if (!accepted) {
      return;
    }

    try {
      await deleteProject(project.id);
      showSuccess('Proyek berhasil dihapus!');
      await goto('/projects');
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  $effect(() => {
    const projectId = $page.params.id;

    if (projectId) {
      void loadProject(projectId);
    }
  });

  $effect(() => {
    if (project && !project.is_cert_projects && activeTab === 'certificates') activeTab = 'detail';
  });

  $effect(() => {
    if (activeTab === 'activity') hasVisitedActivity = true;
    if (activeTab === 'certificates') hasVisitedCertificates = true;
  });

  $effect(() => {
    lockBodyScroll(showEditModal);
    return () => lockBodyScroll(false);
  });
</script>

<svelte:head>
  <title>Detail Project - Indogreen</title>
</svelte:head>

{#if loading}
  <LoadingState label="Memuat proyek..." />
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if project}
  <div class="mb-8 flex w-full min-w-0 flex-col">
    <ProjectHeader
      {project}
      canUpdate={canUpdateProject}
      canDelete={canDeleteProject}
      onEdit={openEditProjectModal}
      onDelete={handleDeleteProject}
    />

    <ProjectTabs bind:activeTab showCertificateTab={project.is_cert_projects} />

    {#if activeTab === 'detail'}
      <div class="mb-8 overflow-hidden bg-white shadow dark:bg-black">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Informasi Project
          </h3>
        </div>
        <div class="border-t border-gray-100 dark:border-gray-700">
          <ProjectDetail {project} />
        </div>
      </div>
    {/if}

    {#if hasVisitedActivity || activeTab === 'activity'}
      <div class:hidden={activeTab !== 'activity'}>
        <ActivityTab {project} />
      </div>
    {/if}

    {#if project.is_cert_projects && (hasVisitedCertificates || activeTab === 'certificates')}
      <div class:hidden={activeTab !== 'certificates'}>
        <CertificateTab {project} />
      </div>
    {/if}
  </div>

  {#if showEditModal}
    <ProjectFormModal
      bind:show={showEditModal}
      bind:form={editProjectForm}
      title="Edit Project"
      submitLabel="Update Project"
      idPrefix="edit_project"
      {customers}
      {projectStatuses}
      {projectKategoris}
      onSubmit={handleSubmitUpdateProject}
    />
  {/if}
{/if}
