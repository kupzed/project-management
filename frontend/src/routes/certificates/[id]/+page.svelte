<script lang="ts">
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import CertificateDetail from '$lib/components/detail/CertificatesDetail.svelte';
  import CertificateFormModal from '$lib/components/form/CertificateFormModal.svelte';
  import {
    deleteCertificate,
    fetchCertificate,
    fetchCertificates,
    updateCertificate
  } from '$lib/services/certificateService';
  import { userPermissions } from '$lib/stores/permissions';
  import { extractApiErrors } from '$lib/utils/errors';
  import { lockBodyScroll } from '$lib/utils/scroll-lock';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type {
    Certificate,
    CertificateEditForm,
    CertificateStatus,
    ExistingAttachment,
    Option,
    ProjectSummary
  } from '$lib/types';

  type CertificateModalForm = Omit<CertificateEditForm, 'attachment_descriptions'> & {
    attachment_descriptions: string[];
  };

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

  let item = $state<Certificate | null>(null);
  let loading = $state(true);
  let error = $state('');
  let projects = $state<ProjectSummary[]>([]);
  let filteredBarangCertificates = $state<Option[]>([]);
  let statuses = $state<CertificateStatus[]>([]);
  let showEditModal = $state(false);
  let form = $state<CertificateModalForm>(makeForm());

  let canUpdateCertificate = $derived(($userPermissions ?? []).includes('certificate-update'));
  let canDeleteCertificate = $derived(($userPermissions ?? []).includes('certificate-delete'));

  async function loadDetail(id: string): Promise<void> {
    loading = true;
    error = '';
    try {
      const result = await fetchCertificate(id);
      item = result.certificate;
      form = makeForm(result.certificate);
      projects = result.formDeps.projects;
      statuses = result.formDeps.statuses;
      filteredBarangCertificates = result.formDeps.barang_options;
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

  function openEditModal(): void {
    if (!item || !canUpdateCertificate) {
      return;
    }

    form = makeForm(item);
    if (item.project_id) {
      void fetchBarangCertificatesByProject(item.project_id);
    } else {
      filteredBarangCertificates = [];
    }
    showEditModal = true;
  }

  function closeEditModal(): void {
    showEditModal = false;
    filteredBarangCertificates = [];
  }

  async function handleSubmitUpdate(): Promise<void> {
    if (!item || !canUpdateCertificate) {
      return;
    }

    try {
      const updated = await updateCertificate(item.id, form);
      item = updated;
      form = makeForm(updated);
      closeEditModal();
      showSuccess('Data berhasil diperbarui!');
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDelete(): Promise<void> {
    if (!item || !canDeleteCertificate) {
      return;
    }

    const accepted = await confirm({
      title: 'Hapus sertifikat?',
      text: 'Apakah Anda yakin ingin menghapus data ini?',
      confirmText: 'Hapus',
      isDangerous: true
    });
    if (!accepted) return;

    try {
      await deleteCertificate(item.id);
      showSuccess('Data berhasil dihapus!');
      await goto('/certificates');
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  $effect(() => {
    const id = $page.params.id;
    if (id) {
      void loadDetail(id);
    }
  });

  $effect(() => {
    lockBodyScroll(showEditModal);
    return () => lockBodyScroll(false);
  });
</script>

<svelte:head>
  <title>Detail Sertifikat - Indogreen</title>
</svelte:head>

{#if loading}
  <LoadingState label="Memuat detail sertifikat..." />
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if !item}
  <p class="text-gray-900 dark:text-white">Data tidak ditemukan.</p>
{:else}
  <div class="mb-8 w-full min-w-0">
    <div class="mb-4 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 flex-1">
        <h2
          class="text-2xl leading-7 font-bold break-words text-gray-900 sm:text-2xl dark:text-white"
        >
          {item.name}
        </h2>
        <div class="my-2 text-sm text-gray-500 dark:text-gray-300">
          <span>No. Sertifikat: {item.no_certificate}</span>
        </div>
      </div>
      <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
        {#if canUpdateCertificate}
          <button
            type="button"
            onclick={openEditModal}
            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm
                   hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:focus:ring-offset-gray-800"
          >
            Edit
          </button>
        {/if}
        {#if canDeleteCertificate}
          <button
            type="button"
            onclick={handleDelete}
            class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm
                   hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none dark:focus:ring-offset-gray-800"
          >
            Hapus
          </button>
        {/if}
      </div>
    </div>

    <div class="overflow-hidden bg-white shadow dark:bg-black">
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
    bind:form
    {projects}
    barangOptions={filteredBarangCertificates}
    {statuses}
    {handleProjectChange}
    allowRemoveAttachment={true}
    onSubmit={handleSubmitUpdate}
    onClose={closeEditModal}
  />
{/if}
