<script lang="ts">
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import MitraDetail from '$lib/components/detail/MitraDetail.svelte';
  import MitraFormModal from '$lib/components/form/MitraFormModal.svelte';
  import { deleteMitra, fetchMitra, updateMitra } from '$lib/services/mitraService';
  import { userPermissions } from '$lib/stores/permissions';
  import { extractApiErrors } from '$lib/utils/errors';
  import { lockBodyScroll } from '$lib/utils/scroll-lock';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type { Mitra, MitraForm } from '$lib/types';
  import MitraCategoryBadges from '../_components/MitraCategoryBadges.svelte';
  import MitraBarangCertificatesTab from '../_components/MitraBarangCertificatesTab.svelte';

  type MitraDetailTab = 'detail' | 'barang';
  type MitraModalForm = Required<
    Omit<
      MitraForm,
      | 'website'
      | 'email'
      | 'kontak_1'
      | 'kontak_1_nama'
      | 'kontak_1_jabatan'
      | 'kontak_2'
      | 'kontak_2_nama'
      | 'kontak_2_jabatan'
    >
  > & {
    website: string;
    email: string;
    kontak_1: string;
    kontak_1_nama: string;
    kontak_1_jabatan: string;
    kontak_2: string;
    kontak_2_nama: string;
    kontak_2_jabatan: string;
  };

  function makeForm(mitraValue?: Mitra): MitraModalForm {
    return {
      nama: mitraValue?.nama ?? '',
      is_pribadi: mitraValue?.is_pribadi ?? false,
      is_perusahaan: mitraValue?.is_perusahaan ?? false,
      is_customer: mitraValue?.is_customer ?? false,
      is_vendor: mitraValue?.is_vendor ?? false,
      alamat: mitraValue?.alamat ?? '',
      website: mitraValue?.website ?? '',
      email: mitraValue?.email ?? '',
      kontak_1: mitraValue?.kontak_1 ?? '',
      kontak_1_nama: mitraValue?.kontak_1_nama ?? '',
      kontak_1_jabatan: mitraValue?.kontak_1_jabatan ?? '',
      kontak_2: mitraValue?.kontak_2 ?? '',
      kontak_2_nama: mitraValue?.kontak_2_nama ?? '',
      kontak_2_jabatan: mitraValue?.kontak_2_jabatan ?? ''
    };
  }

  let mitra = $state<Mitra | null>(null);
  let loading = $state(true);
  let error = $state('');
  let showEditModal = $state(false);
  let activeTab = $state<MitraDetailTab>('detail');
  let hasVisitedBarang = $state(false);
  let form = $state<MitraModalForm>(makeForm());

  let canUpdateMitra = $derived(($userPermissions ?? []).includes('mitra-update'));
  let canDeleteMitra = $derived(($userPermissions ?? []).includes('mitra-delete'));

  async function loadMitra(id: string): Promise<void> {
    loading = true;
    error = '';
    try {
      const result = await fetchMitra(id);
      mitra = result.mitra;
      form = makeForm(result.mitra);
    } catch (err) {
      error = extractApiErrors(err);
    } finally {
      loading = false;
    }
  }

  function openEditModal(): void {
    if (!mitra || !canUpdateMitra) return;
    form = makeForm(mitra);
    showEditModal = true;
  }

  async function handleSubmitUpdate(): Promise<void> {
    if (!mitra || !canUpdateMitra) return;
    try {
      const updated = await updateMitra(mitra.id, form);
      mitra = updated;
      form = makeForm(updated);
      showEditModal = false;
      showSuccess('Mitra berhasil diperbarui!');
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDelete(): Promise<void> {
    if (!mitra || !canDeleteMitra) return;
    const accepted = await confirm({
      title: 'Hapus mitra?',
      text: 'Apakah Anda yakin ingin menghapus mitra ini?',
      confirmText: 'Hapus',
      isDangerous: true
    });
    if (!accepted) return;

    try {
      await deleteMitra(mitra.id);
      showSuccess('Mitra berhasil dihapus!');
      await goto('/mitras');
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  $effect(() => {
    const id = $page.params.id;
    if (id) {
      void loadMitra(id);
    }
  });

  $effect(() => {
    if (activeTab === 'barang') hasVisitedBarang = true;
  });

  $effect(() => {
    lockBodyScroll(showEditModal);
    return () => lockBodyScroll(false);
  });
</script>

<svelte:head>
  <title>Detail Mitra - Indogreen</title>
</svelte:head>

{#if loading}
  <LoadingState label="Memuat detail mitra..." />
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if mitra}
  <div class="mb-8 w-full min-w-0">
    <div class="mb-4 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 flex-1">
        <h2
          class="text-2xl leading-7 font-bold break-words text-gray-900 sm:text-2xl dark:text-white"
        >
          {mitra.nama}
        </h2>
        <div class="my-2 flex items-center text-sm text-gray-500 dark:text-gray-300">
          <MitraCategoryBadges {mitra} />
        </div>
      </div>
      <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
        {#if canUpdateMitra}
          <button
            type="button"
            onclick={openEditModal}
            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm
                   hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:focus:ring-offset-gray-800"
          >
            Edit Mitra
          </button>
        {/if}
        {#if canDeleteMitra}
          <button
            type="button"
            onclick={handleDelete}
            class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm
                   hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none dark:focus:ring-offset-gray-800"
          >
            Hapus Mitra
          </button>
        {/if}
      </div>
    </div>

    <div class="mb-4 flex min-w-0 items-center justify-between">
      <div class="inline-flex rounded-lg bg-gray-200 p-1 dark:bg-gray-700" role="tablist">
        <button
          type="button"
          onclick={() => (activeTab = 'detail')}
          class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-all duration-200 dark:text-gray-200"
          class:bg-white={activeTab === 'detail'}
          class:dark:bg-neutral-900={activeTab === 'detail'}
          class:shadow={activeTab === 'detail'}
          role="tab"
          aria-selected={activeTab === 'detail'}
        >
          Detail
        </button>
        <button
          type="button"
          onclick={() => (activeTab = 'barang')}
          class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-all duration-200 dark:text-gray-200"
          class:bg-white={activeTab === 'barang'}
          class:dark:bg-neutral-900={activeTab === 'barang'}
          class:shadow={activeTab === 'barang'}
          role="tab"
          aria-selected={activeTab === 'barang'}
        >
          Barang
        </button>
      </div>
    </div>

    {#if activeTab === 'detail'}
      <div class="overflow-hidden bg-white shadow dark:bg-black">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Informasi Mitra
          </h3>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700">
          <MitraDetail {mitra} />
        </div>
      </div>
    {/if}

    {#if hasVisitedBarang || activeTab === 'barang'}
      <div class:hidden={activeTab !== 'barang'}>
        <MitraBarangCertificatesTab {mitra} />
      </div>
    {/if}
  </div>

  <MitraFormModal
    bind:show={showEditModal}
    title="Edit Mitra"
    submitLabel="Update Mitra"
    idPrefix="edit_mitra"
    bind:form
    onSubmit={handleSubmitUpdate}
  />
{/if}
