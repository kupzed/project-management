<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { goto } from '$app/navigation';
  import axiosClient from '$lib/axiosClient';
  import BarangCertificatesDetail from '$lib/components/detail/BarangCertificatesDetail.svelte';
  import BarangCertificateFormModal from '$lib/components/form/BarangCertificateFormModal.svelte';
  import { userPermissions } from '$lib/stores/permissions';

  type Mitra = { id: number; nama: string };

  let item: any = null;
  let loading = true;
  let error = '';

  // Dependencies
  let mitras: Mitra[] = [];

  // Edit state
  let showEditModal = false;
  let canUpdateBarangCert = false;
  let canDeleteBarangCert = false;

  $: {
    const perms = $userPermissions ?? [];
    canUpdateBarangCert = perms.includes('bc-update');
    canDeleteBarangCert = perms.includes('bc-delete');
  }

  let form: { name: string; no_seri: string; mitra_id: number | '' | null } = {
    name: '',
    no_seri: '',
    mitra_id: ''
  };

  $: id = $page.params.id;



  async function fetchDetail() {
    loading = true;
    error = '';
    try {
      const res = await axiosClient.get(`/barang-certificates/${id}`);
      item = res.data?.data ?? res.data;
      mitras = res.data?.form_dependencies?.mitras ?? mitras;
    } catch (err: any) {
      error = err.response?.data?.message || 'Gagal memuat detail.';
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    fetchDetail();
  });

  function openEditModal() {
    if (!canUpdateBarangCert) {
      console.warn('User lacks bc-update permission');
      return;
    }
    if (!item) return;
    form = {
      name: item.name ?? '',
      no_seri: item.no_seri ?? '',
      mitra_id: item.mitra_id ?? ''
    };
    showEditModal = true;
  }

  async function handleSubmitUpdate() {
    if (!canUpdateBarangCert) {
      console.warn('Update barang certificate blocked by permission');
      return;
    }
    try {
      await axiosClient.put(`/barang-certificates/${id}`, form);
      alert('Data berhasil diperbarui!');
      showEditModal = false;
      await fetchDetail();
      goto(`/barang-certificates/${id}`);
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal memperbarui data.';
      alert('Error:\n' + messages);
      console.error('Update failed:', err.response || err);
    }
  }

  async function handleDelete() {
    if (!canDeleteBarangCert) {
      console.warn('Delete barang certificate blocked by permission');
      return;
    }
    if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) return;
    try {
      await axiosClient.delete(`/barang-certificates/${id}`);
      alert('Data berhasil dihapus!');
      goto('/barang-certificates');
    } catch (err: any) {
      alert('Gagal menghapus data: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
      console.error('Delete failed:', err.response || err);
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
  <title>Detail Barang Sertifikat - Indogreen</title>
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
          <span>No. Seri: {item.no_seri}</span>
        </div>
      </div>
      <div class="flex flex-col md:flex-row mt-2 mb-4 md:mt-0 md:ml-4 md:mb-4 space-y-2 md:space-y-0 md:space-x-4">
        {#if canUpdateBarangCert}
          <button
            on:click={openEditModal}
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white
                   bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                   dark:focus:ring-offset-gray-800"
          >
            Edit
          </button>
        {/if}
        {#if canDeleteBarangCert}
          <button
            on:click={handleDelete}
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white
                   bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500
                   dark:focus:ring-offset-gray-800"
          >
            Hapus
          </button>
        {/if}
      </div>
    </div>

    <div class="bg-white dark:bg-black shadow overflow-hidden">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Informasi Barang Certificate</h3>
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
    {form}
    {mitras}
    showMitra={true}
    onSubmit={handleSubmitUpdate}
  />
{/if}
