<script lang="ts">
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import BarangCertificatesDetail from '$lib/components/detail/BarangCertificatesDetail.svelte';
  import BarangCertificateFormModal from '$lib/components/form/BarangCertificateFormModal.svelte';
  import {
    deleteBarangCertificate,
    fetchBarangCertificate,
    updateBarangCertificate
  } from '$lib/services/barangCertificateService';
  import { userPermissions } from '$lib/stores/permissions';
  import { extractApiErrors } from '$lib/utils/errors';
  import { lockBodyScroll } from '$lib/utils/scroll-lock';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type { BarangCertificate, BarangCertificateForm, MitraSummary } from '$lib/types';

  type BarangCertificateModalForm = Omit<BarangCertificateForm, 'mitra_id'> & {
    mitra_id: number | '' | null;
  };

  function makeForm(item?: BarangCertificate): BarangCertificateModalForm {
    return {
      name: item?.name ?? '',
      no_seri: item?.no_seri ?? '',
      mitra_id: item?.mitra_id ?? ''
    };
  }

  let item = $state<BarangCertificate | null>(null);
  let loading = $state(true);
  let error = $state('');
  let mitras = $state<MitraSummary[]>([]);
  let showEditModal = $state(false);
  let form = $state<BarangCertificateModalForm>(makeForm());

  let canUpdateBarangCert = $derived(($userPermissions ?? []).includes('bc-update'));
  let canDeleteBarangCert = $derived(($userPermissions ?? []).includes('bc-delete'));

  async function loadDetail(id: string): Promise<void> {
    loading = true;
    error = '';
    try {
      const result = await fetchBarangCertificate(id);
      item = result.barangCertificate;
      form = makeForm(result.barangCertificate);
      mitras = result.formDeps.mitras;
    } catch (err) {
      error = extractApiErrors(err);
    } finally {
      loading = false;
    }
  }

  function openEditModal(): void {
    if (!item || !canUpdateBarangCert) return;
    form = makeForm(item);
    showEditModal = true;
  }

  async function handleSubmitUpdate(): Promise<void> {
    if (!item || !canUpdateBarangCert) return;
    try {
      const updated = await updateBarangCertificate(item.id, form as BarangCertificateForm);
      item = updated;
      form = makeForm(updated);
      showEditModal = false;
      showSuccess('Data berhasil diperbarui!');
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDelete(): Promise<void> {
    if (!item || !canDeleteBarangCert) return;
    const accepted = await confirm({
      title: 'Hapus barang certificate?',
      text: 'Apakah Anda yakin ingin menghapus data ini?',
      confirmText: 'Hapus',
      isDangerous: true
    });
    if (!accepted) return;

    try {
      await deleteBarangCertificate(item.id);
      showSuccess('Data berhasil dihapus!');
      await goto('/barang-certificates');
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
  <title>Detail Barang Sertifikat - Indogreen</title>
</svelte:head>

{#if loading}
  <LoadingState label="Memuat detail barang sertifikat..." />
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
          <span>No. Seri: {item.no_seri}</span>
        </div>
      </div>
      <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
        {#if canUpdateBarangCert}
          <button
            type="button"
            onclick={openEditModal}
            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm
                   hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:focus:ring-offset-gray-800"
          >
            Edit
          </button>
        {/if}
        {#if canDeleteBarangCert}
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
          Informasi Barang Certificate
        </h3>
      </div>
      <div class="border-t border-gray-200 dark:border-gray-700">
        <BarangCertificatesDetail barangCertificates={item} />
      </div>
    </div>
  </div>

  <BarangCertificateFormModal
    bind:show={showEditModal}
    title="Edit Barang Certificate"
    submitLabel="Update"
    idPrefix="edit"
    bind:form
    {mitras}
    showMitra={true}
    onSubmit={handleSubmitUpdate}
  />
{/if}
