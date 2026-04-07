<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { goto } from '$app/navigation';
  import axiosClient from '$lib/axiosClient';
  import CertificateFormModal from '$lib/components/form/CertificateFormModal.svelte';
  import CertificateDetail from '$lib/components/detail/CertificatesDetail.svelte';
  import { userPermissions } from '$lib/stores/permissions';

  /**
   * Types for project options and attachment items. Existing attachments now
   * include optional description and original_name fields so the user can
   * modify them in the edit form.
   */
  type Option = { id: number; name?: string; title?: string; no_seri?: string };
  type AttachmentItem = {
    id: number;
    name: string;
    description?: string;
    original_name?: string;
    url: string;
    size?: number;
  };

  let item: any = null;
  let loading = true;
  let error = '';

  let projects: Option[] = [];
  let barangCertificates: Option[] = [];
  let filteredBarangCertificates: Option[] = [];

  // permissions
  let canUpdateCertificate = false;
  let canDeleteCertificate = false;

  $: {
    const perms = $userPermissions ?? [];
    canUpdateCertificate = perms.includes('certificate-update');
    canDeleteCertificate = perms.includes('certificate-delete');
  }

  // control display of edit modal
  let showEditModal = false;

  /**
   * Form state for editing a certificate. This includes arrays for new file
   * uploads (attachments, attachment_names, attachment_descriptions),
   * as well as existing attachments that can be renamed or have their
   * descriptions updated.
   */
  let form: {
    name: string;
    no_certificate: string;
    project_id: number | '' | null;
    barang_certificate_id: number | '' | null;
    status: string;
    date_of_issue: string;
    date_of_expired: string;
    attachments: File[];
    attachment_names: string[];
    attachment_descriptions: string[];
    existing_attachments: AttachmentItem[];
    removed_existing_ids: number[];
  } = {
    name: '',
    no_certificate: '',
    project_id: '',
    barang_certificate_id: '',
    status: '',
    date_of_issue: '',
    date_of_expired: '',
    attachments: [],
    attachment_names: [],
    attachment_descriptions: [],
    existing_attachments: [],
    removed_existing_ids: []
  };

  let statuses: string[] = [];

  $: id = $page.params.id;

  // Dependencies are fetched alongside detail via form_dependencies

  // Fetch certificates by project when user selects a different project
  async function fetchBarangCertificatesByProject(projectId: number) {
    if (!projectId) {
      filteredBarangCertificates = [];
      return;
    }
    try {
      // Panggil endpoint utama /certificates dengan filter project_id
      // Agar kita bisa mendapat form_dependencies yang terfilter secara otomatis
      const res = await axiosClient.get('/certificates', { params: { project_id: projectId, per_page: 5 } });
      const root = res.data ?? {};
      const formDeps = root.form_dependencies ?? root.meta?.form_dependencies ?? {};
      filteredBarangCertificates = formDeps.barang_options ?? [];
    } catch (err) {
      console.error('Failed to fetch barang certificates by project', err);
      filteredBarangCertificates = [];
    }
  }

  function handleProjectChange(projectId: number | '' | null) {
    if (projectId) {
      fetchBarangCertificatesByProject(Number(projectId));
      form.barang_certificate_id = '';
    } else {
      filteredBarangCertificates = [];
      form.barang_certificate_id = '';
    }
  }

  function closeEditModal() {
    showEditModal = false;
    filteredBarangCertificates = [];
  }

  // Fetch a certificate's details when opening the page
  async function fetchDetail() {
    loading = true;
    error = '';
    try {
      const res = await axiosClient.get(`/certificates/${id}`);
      const root = res.data ?? {};
      item = root.data ?? root;

      const formDeps = root.form_dependencies ?? root.meta?.form_dependencies ?? {};
      if (formDeps.projects) projects = formDeps.projects;
      if (formDeps.barang_certificates) barangCertificates = formDeps.barang_certificates;
      if (formDeps.statuses) statuses = formDeps.statuses;
      if (formDeps.barang_options) filteredBarangCertificates = formDeps.barang_options;
    } catch (err: any) {
      error = err.response?.data?.message || 'Gagal memuat detail.';
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    fetchDetail();
  });

  /**
   * Initialize the form with the current certificate details and open the edit modal.
   * Existing attachments are mapped to include description and original_name to allow editing.
   */
  function openEditModal() {
    if (!canUpdateCertificate) {
      console.warn('User lacks certificate-update permission');
      return;
    }
    if (!item) return;
    form = {
      name: item.name ?? '',
      no_certificate: item.no_certificate ?? '',
      project_id: item.project_id ?? '',
      barang_certificate_id: item.barang_certificate_id ?? '',
      status: item.status ?? '',
      date_of_issue: item.date_of_issue
        ? new Date(item.date_of_issue).toISOString().split('T')[0]
        : '',
      date_of_expired: item.date_of_expired
        ? new Date(item.date_of_expired).toISOString().split('T')[0]
        : '',
      attachments: [],
      attachment_names: [],
      attachment_descriptions: [],
      existing_attachments: Array.isArray(item.attachments)
        ? item.attachments.map((a: any) => ({
            id: a.id,
            name: a.name ?? a.file_name ?? 'Lampiran',
            // include description for editing
            description: a.description ?? '',
            // show original file name if available; fall back to file_name or assigned name
            original_name: a.original_name ?? a.file_name ?? a.name ?? '',
            url: a.url ?? a.path ?? a.file_path,
            size: a.size
          }))
        : [],
      removed_existing_ids: []
    };
    if (item.project_id && typeof item.project_id === 'number') {
      fetchBarangCertificatesByProject(item.project_id);
    } else {
      filteredBarangCertificates = [];
    }
    showEditModal = true;
  }

  function appendScalar(fd: FormData, key: string, val: any) {
    if (val === null || val === undefined || val === '') return;
    fd.append(key, String(val));
  }

  /**
   * Build a FormData object for updating a certificate. Handles new attachments,
   * removal of existing ones, and editing of existing attachment names/descriptions.
   */
  function buildFormData() {
    const fd = new FormData();
    appendScalar(fd, 'name', form.name);
    appendScalar(fd, 'no_certificate', form.no_certificate);
    appendScalar(fd, 'project_id', form.project_id);
    appendScalar(fd, 'barang_certificate_id', form.barang_certificate_id);
    appendScalar(fd, 'status', form.status);
    appendScalar(fd, 'date_of_issue', form.date_of_issue);
    appendScalar(fd, 'date_of_expired', form.date_of_expired);
    // new attachments
    (form.attachments || []).forEach((file, i) => fd.append(`attachments[${i}]`, file));
    (form.attachment_names || []).forEach((n, i) => {
      if (n != null) fd.append(`attachment_names[${i}]`, n);
    });
    (form.attachment_descriptions || []).forEach((d, i) => {
      if (d != null) fd.append(`attachment_descriptions[${i}]`, d);
    });
    // removed existing attachments
    (form.removed_existing_ids || []).forEach((id) => fd.append('removed_existing_ids[]', String(id)));
    // edits to existing attachments
    (form.existing_attachments || []).forEach((att, i) => {
      if (att.id != null) fd.append(`existing_attachment_ids[${i}]`, String(att.id));
      if (att.name != null) fd.append(`existing_attachment_names[${i}]`, att.name);
      if (att.description != null) fd.append(`existing_attachment_descriptions[${i}]`, att.description);
    });
    return fd;
  }

  async function handleSubmitUpdate() {
    if (!canUpdateCertificate) {
      console.warn('Update certificate blocked by permission');
      return;
    }
    try {
      const fd = buildFormData();
      fd.append('_method', 'PUT');
      await axiosClient.post(`/certificates/${id}`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      alert('Data berhasil diperbarui!');
      closeEditModal();
      await fetchDetail();
      goto(`/certificates/${id}`);
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal memperbarui data.';
      alert('Error:\n' + messages);
    }
  }

  async function handleDelete() {
    if (!canDeleteCertificate) {
      console.warn('Delete certificate blocked by permission');
      return;
    }
    if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) return;
    try {
      await axiosClient.delete(`/certificates/${id}`);
      alert('Data berhasil dihapus!');
      goto('/certificates');
    } catch (err: any) {
      alert('Gagal menghapus data: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
    }
  }

  // --- kunci scroll saat membuka drawer & modal ---
  function lockBodyScroll(lock: boolean) {
    const body = document.body;
    if (!body) return;
    if (lock) {
      const scrollY = window.scrollY;
      body.dataset.scrollY = String(scrollY);
      body.style.position = 'fixed';
      body.style.top = `-${scrollY}px`;
      body.style.left = '0';
      body.style.right = '0';
      body.style.overflow = 'hidden';
      body.style.width = '100%';
    } else {
      const y = Number(body.dataset.scrollY || '0');
      body.style.position = '';
      body.style.top = '';
      body.style.left = '';
      body.style.right = '';
      body.style.overflow = '';
      body.style.width = '';
      delete body.dataset.scrollY;
      window.scrollTo(0, y);
    }
  }
  $: lockBodyScroll(showEditModal);
</script>

<svelte:head>
  <title>Detail Sertifikat - Indogreen</title>
</svelte:head>

{#if loading}
  <p class="text-gray-900 dark:text-white">Memuat detail...</p>
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if !item}
  <p class="text-gray-900 dark:text-white">Data tidak ditemukan.</p>
{:else}
  <div class="max-w-1xl mx-auto mb-8">
    <div class="flex justify-between items-center mb-4">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-2xl">
          {item.name}
        </h2>
        <div class="my-2 text-sm text-gray-500 dark:text-gray-300">
          <span>No. Sertifikat: {item.no_certificate}</span>
        </div>
      </div>
      <div
        class="flex flex-col md:flex-row mt-2 mb-4 md:mt-0 md:ml-4 md:mb-4 space-y-2 md:space-y-0 md:space-x-4"
      >
        {#if canUpdateCertificate}
          <button
            on:click={openEditModal}
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
          >
            Edit
          </button>
        {/if}
        {#if canDeleteCertificate}
          <button
            on:click={handleDelete}
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800"
          >
            Hapus
          </button>
        {/if}
      </div>
    </div>

    <div class="bg-white dark:bg-black shadow overflow-hidden">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
          Informasi Sertifikat
        </h3>
      </div>
      <div class="border-t border-gray-200 dark:border-gray-700">
        <CertificateDetail certificates={item} />
      </div>
    </div>
  </div>

  <CertificateFormModal
    bind:show={showEditModal}
    title="Edit Certificate"
    submitLabel="Update"
    idPrefix="edit"
    {form}
    {projects}
    barangOptions={filteredBarangCertificates}
    statuses={Array.from(statuses)}
    handleProjectChange={handleProjectChange}
    allowRemoveAttachment={true}
    onSubmit={handleSubmitUpdate}
    onClose={closeEditModal}
  />
{/if}