<script lang="ts">
  import { page } from '$app/stores';
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import axiosClient from '$lib/axiosClient';
  import ActivityDetail from '$lib/components/detail/ActivityDetail.svelte';
  import ActivityFormModal from '$lib/components/form/ActivityFormModal.svelte';
  import { userPermissions } from '$lib/stores/permissions';

  // local state for the current activity id and loaded record
  let activityId: string | null = null;
  let activity: any = null;
  let projects: any[] = [];
  let vendors: any[] = [];
  let loadingActivity = true;
  let errorActivity = '';

  // permissions
  let canUpdateActivity = false;
  let canDeleteActivity = false;

  $: {
    const perms = $userPermissions ?? [];
    canUpdateActivity = perms.includes('activity-update');
    canDeleteActivity = perms.includes('activity-delete');
  }

  // show/hide the edit modal
  let showEditModal: boolean = false;

  /**
   * The form state mirrors the API payload for creating/updating an activity.
   * It supports both new attachments (attachments, attachment_names, attachment_descriptions)
   * and editing existing attachments (existing_attachments with id/name/description/original_name).
   */
  let form: {
    name: string;
    short_desc: string;
    description: string;
    project_id: string | number | '';
    kategori: string | '';
    value: number;
    activity_date: string | '';
    jenis: string | '';
    mitra_id: number | string | '' | null;
    from?: string | '';
    to?: string | '';
    attachments: File[];
    attachment_names: string[];
    attachment_descriptions: string[];
    existing_attachments?: Array<{
      id: number;
      name: string;
      description?: string;
      original_name?: string;
      url: string;
      size?: number;
    }>;
    removed_existing_ids?: number[];
  } = {
    name: '',
    short_desc: '',
    description: '',
    project_id: '',
    kategori: '',
    value: 0,
    activity_date: '',
    jenis: '',
    mitra_id: null,
    from: '',
    to: '',
    attachments: [],
    attachment_names: [],
    attachment_descriptions: [],
    existing_attachments: [],
    removed_existing_ids: []
  };

  // list kategori & jenis diisi dari backend
  let activityKategoriList: string[] = [];
  let activityJenisList: string[] = [];

  // fetch the activity details from the server and populate the form
  async function fetchActivityDetails() {
    loadingActivity = true;
    errorActivity = '';
    activityId = $page.params.id;
    if (!activityId) {
      errorActivity = 'Activity ID tidak ditemukan.';
      loadingActivity = false;
      return;
    }
    try {
      const response = await axiosClient.get(`/activities/${activityId}`);
      activity = response.data.data;
      // Populate the form using the returned activity record
      form = {
        name: activity.name ?? '',
        short_desc: activity.short_desc ?? '',
        description: activity.description ?? '',
        project_id: activity.project_id || '',
        kategori: activity.kategori || '',
        value: activity.kategori || 0,
        activity_date: activity.activity_date
          ? new Date(activity.activity_date).toISOString().split('T')[0]
          : '',
        jenis: activity.jenis || '',
        mitra_id: activity.mitra_id || null,
        from: activity.from || '',
        to: activity.to || '',
        attachments: [],
        attachment_names: [],
        attachment_descriptions: [],
        // map existing attachments to include description and original_name for editing
        existing_attachments: Array.isArray(activity.attachments)
          ? activity.attachments.map((a: any) => ({
              id: a.id,
              // use assigned name or fallback to file_name; ensure a default label
              name: a.name ?? a.file_name ?? 'Lampiran',
              // keep description for editing
              description: a.description ?? '',
              // show original file name if available; fall back to file_name or name
              original_name: a.original_name ?? a.file_name ?? a.name ?? '',
              url: a.url ?? a.path ?? a.file_path,
              size: a.size
            }))
          : [],
        removed_existing_ids: []
      };

      // update mitra_id depending on jenis and selected project
      if (form.jenis === 'Customer' && form.project_id) {
        const selectedProject = projects.find((p) => p.id == form.project_id);
        if (selectedProject?.mitra_id) form.mitra_id = selectedProject.mitra_id;
      }

      // Extract form dependencies from the same response
      if (response.data.form_dependencies) {
        const deps = response.data.form_dependencies;
        projects = deps.projects || [];
        vendors = deps.vendors || [];
        // Optional customers fetch isn't provided by default unless it's in the payload. Backend returns customers via getFormDependenciesArray.
        let customers = deps.customers || [];
        
        activityKategoriList = Array.isArray(deps.kategori_list) ? deps.kategori_list : [];
        activityJenisList = Array.isArray(deps.jenis_list) ? deps.jenis_list : [];
        
        if (Array.isArray(projects) && Array.isArray(vendors)) {
          const vendorMap = new Map(vendors.map((v: any) => [v.id, v]));
          // Map customers too
          const customerMap = new Map(customers.map((c: any) => [c.id, c]));
          projects = projects.map((p: any) => ({
            ...p,
            mitra: p.mitra || (p.mitra_id ? vendorMap.get(p.mitra_id) : (p.customer_id ? customerMap.get(p.customer_id) : undefined))
          }));
        }
      }

    } catch (err: any) {
      errorActivity =
        err.response?.data?.message || 'Gagal memuat detail aktivitas.';
      console.error('Error fetching activity details:', err.response || err);
    } finally {
      loadingActivity = false;
    }
  }



  onMount(() => {
    fetchActivityDetails();
  });

  function openEditModal() {
    if (!canUpdateActivity) {
      console.warn('User lacks activity-update permission');
      return;
    }
    showEditModal = true;
  }

  function appendScalar(fd: FormData, key: string, val: any) {
    if (val === null || val === undefined || val === '') return;
    fd.append(key, String(val));
  }

  /**
   * Build the multipart FormData payload for updating an activity.
   * Includes both new file uploads and updates to existing attachment names/descriptions.
   */
  function buildFormDataForActivity() {
    const fd = new FormData();
    appendScalar(fd, 'name', form.name);
    appendScalar(fd, 'short_desc', form.short_desc);
    appendScalar(fd, 'description', form.description);
    appendScalar(fd, 'project_id', form.project_id);
    appendScalar(fd, 'kategori', form.kategori);
    appendScalar(fd, 'value', form.value);
    appendScalar(fd, 'activity_date', form.activity_date);
    appendScalar(fd, 'jenis', form.jenis);
    appendScalar(fd, 'from', form.from);
    appendScalar(fd, 'to', form.to);
    // Determine mitra_id based on jenis
    if (form.jenis === 'Internal') {
      fd.set('mitra_id', '1');
    } else if (form.jenis === 'Customer') {
      const selectedProject = projects.find((p) => p.id == form.project_id);
      if (selectedProject?.mitra_id) fd.set('mitra_id', String(selectedProject.mitra_id));
    } else if (form.jenis === 'Vendor' && form.mitra_id) {
      fd.set('mitra_id', String(form.mitra_id));
    }
    // append new attachments
    (form.attachments || []).forEach((file, i) => fd.append(`attachments[${i}]`, file));
    (form.attachment_names || []).forEach((n, i) => {
      if (n != null) fd.append(`attachment_names[${i}]`, n);
    });
    (form.attachment_descriptions || []).forEach((d, i) => {
      if (d != null) fd.append(`attachment_descriptions[${i}]`, d);
    });
    // removed ids
    (form.removed_existing_ids || []).forEach((id) => fd.append('removed_existing_ids[]', String(id)));
    // existing attachment edits
    (form.existing_attachments || []).forEach((att, i) => {
      if (att.id != null) fd.append(`existing_attachment_ids[${i}]`, String(att.id));
      if (att.name != null) fd.append(`existing_attachment_names[${i}]`, att.name);
      if (att.description != null) fd.append(`existing_attachment_descriptions[${i}]`, att.description);
    });
    return fd;
  }

  async function handleSubmitUpdate() {
    if (!activity?.id) return;
    if (!canUpdateActivity) {
      console.warn('Update activity blocked by permission');
      return;
    }

    try {
      const formData = buildFormDataForActivity();
      formData.append('_method', 'PUT');
      await axiosClient.post(`/activities/${activity.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      alert('Aktivitas berhasil diperbarui!');
      goto(`/activities/${activity.id}`);
      showEditModal = false;
      fetchActivityDetails();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal memperbarui aktivitas.';
      alert('Error:\n' + messages);
      console.error('Update activity failed:', err.response || err);
    }
  }

  async function handleDelete() {
    if (!activity?.id) return;
    if (!confirm('Apakah Anda yakin ingin menghapus aktivitas ini?')) return;
    try {
      await axiosClient.delete(`/activities/${activity.id}`);
      alert('Aktivitas berhasil dihapus!');
      goto('/activities');
    } catch (err: any) {
      alert(
        'Gagal menghapus aktivitas: ' + (err.response?.data?.message || 'Terjadi kesalahan')
      );
      console.error('Delete activity failed:', err.response || err);
    }
  }

  // Reactive statements to keep mitra_id in sync when jenis changes or when the modal is closed
  let previousJenis = '';
  $: if (showEditModal && form.jenis && form.jenis !== previousJenis && projects.length > 0) {
    previousJenis = form.jenis;
    if (form.jenis === 'Customer') {
      const selectedProject = projects.find((p) => p.id == form.project_id);
      form.mitra_id = selectedProject?.mitra_id || null;
    } else if (form.jenis === 'Internal') {
      form.mitra_id = '1';
    } else if (form.jenis === 'Vendor') {
      if (!Array.isArray(vendors) || !vendors.some((v) => v.id == form.mitra_id)) form.mitra_id = '';
    } else {
      form.mitra_id = null;
    }
  }
  $: if (form.jenis === 'Customer' && form.project_id && projects.length > 0) {
    const selectedProject = projects.find((p) => p.id == form.project_id);
    if (selectedProject?.mitra_id && form.mitra_id !== selectedProject.mitra_id) form.mitra_id = selectedProject.mitra_id;
  }
  $: if (!showEditModal) {
    previousJenis = '';
  }
</script>

<svelte:head>
  <title>Detail Activity - Indogreen</title>
</svelte:head>

{#if loadingActivity}
  <p class="text-gray-900 dark:text-white">Memuat detail aktivitas...</p>
{:else if errorActivity}
  <p class="text-red-500">{errorActivity}</p>
{:else if activity}
  <div class="max-w-1xl mx-auto mb-8">
    <div class="flex justify-between items-center mb-4">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-2xl">
          {activity.name}
        </h2>
        <div class="my-2 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
          <div class="my-2 flex items-center text-sm text-gray-500 dark:text-gray-300">
            <svg
              class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                clip-rule="evenodd"
              />
            </svg>
            Aktivitas: {new Date(activity.activity_date).toLocaleDateString('id-ID', {
              day: '2-digit',
              month: 'long',
              year: 'numeric'
            })}
          </div>
          <div class="my-2 flex items-center text-sm">
            <span
              class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-300"
            >
              {activity.kategori}
            </span>
          </div>
        </div>
      </div>
      <div
        class="flex flex-col md:flex-row mt-2 mb-4 md:mt-0 md:ml-4 md:mb-4 space-y-2 md:space-y-0 md:space-x-4"
      >
        {#if canUpdateActivity}
          <button
            on:click={openEditModal}
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
          >
            Edit Aktivitas
          </button>
        {/if}
        {#if canDeleteActivity}
          <button
            on:click={handleDelete}
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800"
          >
            Hapus Aktivitas
          </button>
        {/if}
      </div>
    </div>

    <div class="bg-white dark:bg-black shadow overflow-hidden">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
          Informasi Aktivitas
        </h3>
      </div>
      <div class="border-t border-gray-200 dark:border-gray-700">
        <ActivityDetail activity={activity} />
      </div>
    </div>
  </div>
{/if}

{#if activity}
  <ActivityFormModal
    bind:show={showEditModal}
    title="Edit Aktivitas"
    submitLabel="Update Aktivitas"
    idPrefix="edit_activity"
    {form}
    {projects}
    {vendors}
    {activityKategoriList}
    {activityJenisList}
    allowRemoveAttachment={true}
    onSubmit={handleSubmitUpdate}
  />
{/if}