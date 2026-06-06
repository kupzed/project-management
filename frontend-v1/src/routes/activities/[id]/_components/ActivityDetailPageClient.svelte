<script lang="ts">
  import { SvelteMap } from 'svelte/reactivity';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import ActivityDetail from '$lib/components/detail/ActivityDetail.svelte';
  import ActivityFormModal from '$lib/components/form/ActivityFormModal.svelte';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import { deleteActivity, fetchActivity, updateActivity } from '$lib/services/activityService';
  import { userPermissions } from '$lib/stores/permissions';
  import { extractApiErrors } from '$lib/utils/errors';
  import { formatDate } from '$lib/utils/formatters';
  import { lockBodyScroll } from '$lib/utils/scroll-lock';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type {
    Activity,
    ActivityEditForm,
    ActivityForm,
    ActivityJenis,
    ActivityKategori,
    ExistingAttachment,
    Option,
    ProjectSummary
  } from '$lib/types';

  type NamedOption = { id: number; nama: string; email: string | null };
  type ActivityProjectOption = ProjectSummary & {
    mitra_id: number | null;
    mitra?: NamedOption | null;
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

  function optionName(option: Option): string {
    return option.nama ?? option.name ?? option.title ?? option.no_seri ?? String(option.id);
  }

  function toNamedOptions(options: Option[]): NamedOption[] {
    return options.map((option) => ({ id: option.id, nama: optionName(option), email: null }));
  }

  function normalizeExistingAttachments(
    attachments: Activity['attachments']
  ): ExistingAttachment[] {
    return (attachments ?? []).flatMap((attachment) => {
      if (typeof attachment.id !== 'number') return [];
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

  function makeForm(activityValue?: Activity): ActivityModalEditForm {
    return {
      name: activityValue?.name ?? '',
      short_desc: activityValue?.short_desc ?? '',
      description: activityValue?.description ?? '',
      project_id: activityValue?.project_id ?? '',
      kategori: activityValue?.kategori ?? '',
      value: activityValue?.value ?? 0,
      activity_date: activityValue?.activity_date
        ? new Date(activityValue.activity_date).toISOString().split('T')[0]
        : '',
      jenis: activityValue?.jenis ?? '',
      mitra_id: activityValue?.mitra_id ?? null,
      from: activityValue?.from ?? '',
      to: activityValue?.to ?? '',
      attachments: [],
      attachment_names: [],
      attachment_descriptions: [],
      existing_attachments: normalizeExistingAttachments(activityValue?.attachments ?? []),
      removed_existing_ids: []
    };
  }

  function withProjectMitra(
    depsProjects: Array<ProjectSummary & { mitra_id: number | null }>,
    vendors: NamedOption[],
    customers: NamedOption[]
  ): ActivityProjectOption[] {
    const mitraMap = new SvelteMap<number, NamedOption>();
    vendors.forEach((vendor) => mitraMap.set(vendor.id, vendor));
    customers.forEach((customer) => mitraMap.set(customer.id, customer));

    return depsProjects.map((project) => ({
      ...project,
      mitra: project.mitra_id ? (mitraMap.get(project.mitra_id) ?? null) : null
    }));
  }

  let activity = $state<Activity | null>(null);
  let projects = $state<ActivityProjectOption[]>([]);
  let vendors = $state<NamedOption[]>([]);
  let loadingActivity = $state(true);
  let errorActivity = $state('');
  let showEditModal = $state(false);
  let form = $state<ActivityModalEditForm>(makeForm());
  let activityKategoriList = $state<ActivityKategori[]>([]);
  let activityJenisList = $state<ActivityJenis[]>([]);
  let previousJenis = $state('');
  let lastLoadedId = $state('');

  let canUpdateActivity = $derived(($userPermissions ?? []).includes('activity-update'));
  let canDeleteActivity = $derived(($userPermissions ?? []).includes('activity-delete'));

  function projectMitraId(projectId: number | string | ''): number | undefined {
    const selectedProject = projects.find((project) => project.id === Number(projectId));
    return selectedProject?.mitra_id ?? undefined;
  }

  async function loadActivity(activityId: string): Promise<void> {
    loadingActivity = true;
    errorActivity = '';
    try {
      const payload = await fetchActivity(activityId);
      const vendorOptions = toNamedOptions(payload.formDeps.vendors);
      const customerOptions = toNamedOptions(payload.formDeps.customers);
      activity = payload.activity;
      vendors = vendorOptions;
      projects = withProjectMitra(payload.formDeps.projects, vendorOptions, customerOptions);
      activityKategoriList = payload.formDeps.kategoriList;
      activityJenisList = payload.formDeps.jenisList;
      form = makeForm(payload.activity);
    } catch (err: unknown) {
      errorActivity = extractApiErrors(err);
      showError(errorActivity);
    } finally {
      loadingActivity = false;
    }
  }

  function openEditModal(): void {
    if (!canUpdateActivity) return;
    showEditModal = true;
  }

  async function handleSubmitUpdate(): Promise<void> {
    if (!activity?.id || !canUpdateActivity) return;
    try {
      await updateActivity(activity.id, form as ActivityEditForm, projectMitraId(form.project_id));
      showSuccess('Aktivitas berhasil diperbarui!');
      showEditModal = false;
      await loadActivity(String(activity.id));
    } catch (err: unknown) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDelete(): Promise<void> {
    if (!activity?.id || !canDeleteActivity) return;
    const confirmed = await confirm({
      title: 'Hapus aktivitas?',
      text: 'Aktivitas yang dihapus tidak dapat dikembalikan.',
      confirmText: 'Hapus',
      isDangerous: true
    });
    if (!confirmed) return;

    try {
      await deleteActivity(activity.id);
      showSuccess('Aktivitas berhasil dihapus!');
      await goto('/activities');
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
      form.mitra_id = null;
    }
  }

  $effect(() => {
    const activityId = $page.params.id;
    if (activityId && activityId !== lastLoadedId) {
      lastLoadedId = activityId;
      void loadActivity(activityId);
    }
  });

  $effect(() => {
    if (!showEditModal) {
      previousJenis = '';
      return;
    }

    if (form.jenis !== previousJenis) {
      previousJenis = form.jenis;
      syncMitraFromJenis();
      return;
    }

    if (form.jenis === 'Customer' && form.project_id) {
      syncMitraFromJenis();
    }
  });

  $effect(() => {
    lockBodyScroll(showEditModal);
    return () => lockBodyScroll(false);
  });
</script>

<svelte:head>
  <title>Detail Activity - Indogreen</title>
</svelte:head>

{#if loadingActivity}
  <LoadingState label="Memuat detail aktivitas..." />
{:else if errorActivity}
  <p class="text-red-500">{errorActivity}</p>
{:else if activity}
  <div class="mb-8 w-full min-w-0">
    <div class="mb-4 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 flex-1">
        <h2
          class="text-2xl leading-7 font-bold break-words text-gray-900 sm:text-2xl dark:text-white"
        >
          {activity.name}
        </h2>
        <div class="my-2 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
          <div class="my-2 flex items-center text-sm text-gray-500 dark:text-gray-300">
            <svg
              class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400"
              fill="currentColor"
              viewBox="0 0 20 20"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                clip-rule="evenodd"
              ></path>
            </svg>
            Aktivitas: {activity.activity_date ? formatDate(activity.activity_date, 'long') : '-'}
          </div>
          <div class="my-2 flex items-center text-sm">
            <span
              class="inline-flex rounded-full bg-gray-300 px-2 text-xs leading-5 font-semibold text-gray-900 dark:bg-gray-700 dark:text-gray-300"
            >
              {activity.kategori}
            </span>
          </div>
        </div>
      </div>
      <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
        {#if canUpdateActivity}
          <button
            type="button"
            onclick={openEditModal}
            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm
                   hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:focus:ring-offset-gray-800"
          >
            Edit Aktivitas
          </button>
        {/if}
        {#if canDeleteActivity}
          <button
            type="button"
            onclick={handleDelete}
            class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm
                   hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none dark:focus:ring-offset-gray-800"
          >
            Hapus Aktivitas
          </button>
        {/if}
      </div>
    </div>

    <div class="overflow-hidden bg-white shadow dark:bg-black">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
          Informasi Aktivitas
        </h3>
      </div>
      <div class="border-t border-gray-200 dark:border-gray-700">
        <ActivityDetail {activity} />
      </div>
    </div>
  </div>
{/if}

{#if activity}
  <ActivityFormModal
    bind:show={showEditModal}
    bind:form
    title="Edit Aktivitas"
    submitLabel="Update Aktivitas"
    idPrefix="edit_activity"
    {projects}
    {vendors}
    {activityKategoriList}
    {activityJenisList}
    allowRemoveAttachment={true}
    onSubmit={handleSubmitUpdate}
  />
{/if}
