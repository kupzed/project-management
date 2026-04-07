<script lang="ts">
  import { page } from '$app/stores';
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import axiosClient from '$lib/axiosClient';
  import Drawer from '$lib/components/Drawer.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import BarangCertificatesDetail from '$lib/components/detail/BarangCertificatesDetail.svelte';
  import MitraDetail from '$lib/components/detail/MitraDetail.svelte';
  import MitraFormModal from '$lib/components/form/MitraFormModal.svelte';
  import BarangCertificateFormModal from '$lib/components/form/BarangCertificateFormModal.svelte';
  import { userPermissions } from '$lib/stores/permissions';

  let mitraId: string | null = null;
  let mitra: any = null;
  let loadingMitra = true;
  let errorMitra = '';

  // Modal state for Update
  let showEditModal: boolean = false;

  // Form data for Update
  let form = {
    nama: '',
    is_pribadi: false,
    is_perusahaan: false,
    is_customer: false,
    is_vendor: false,
    alamat: '',
    website: '',
    email: '',
    kontak_1: '',
    kontak_1_nama: '',
    kontak_1_jabatan: '',
    kontak_2: '',
    kontak_2_nama: '',
    kontak_2_jabatan: '',
  };

  let editingMitra: any = true;

  let mitraKategoriOptions: Array<{value: string, label: string}> = [];

  // Permissions
  let canUpdateMitra = false;
  let canDeleteMitra = false;
  let canCreateBarangCert = false;
  let canUpdateBarangCert = false;
  let canDeleteBarangCert = false;

  $: {
    const perms = $userPermissions ?? [];
    canUpdateMitra = perms.includes('mitra-update');
    canDeleteMitra = perms.includes('mitra-delete');
    canCreateBarangCert = perms.includes('bc-create');
    canUpdateBarangCert = perms.includes('bc-update');
    canDeleteBarangCert = perms.includes('bc-delete');
  }

  // Tabs state
  let activeTab: 'detail' | 'barang' = 'detail';

  // Barang Certificates (per mitra)
  type BarangCertificate = {
    id: number;
    name: string;
    no_seri: string;
    mitra_id: number | '' | null;
    mitra?: { id: number; nama: string } | null;
    created_at?: string;
    updated_at?: string;
  };

  let bcItems: BarangCertificate[] = [];
  let bcLoading = false;
  let bcError = '';
  let bcSearch = '';
  let bcCurrentPage = 1;
  let bcLastPage = 1;
  let bcTotalItems = 0;
  let perPage: number = 50;
  const perPageOptions = [10, 25, 50, 100];
  let bcInitialized = false;

  // Barang Certificates: modal and drawer state
  let bcShowCreateModal = false;
  let bcShowEditModal = false;
  let bcEditingItem: BarangCertificate | null = null;
  let bcShowDetailDrawer = false;
  let bcSelectedItem: BarangCertificate | null = null;
  let bcActiveView: 'table' | 'list' = 'table';
  const views: Array<'table' | 'list'> = ['table', 'list'];

  // sort state (default created desc)
  let bcSortBy: 'created' = 'created';
  let bcSortDir: 'asc' | 'desc' = 'desc';


  function handleViewKeydown(e: KeyboardEvent) {
    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
      e.preventDefault();
      let idx = views.indexOf(bcActiveView);
      idx = e.key === 'ArrowRight' ? (idx + 1) % views.length : (idx - 1 + views.length) % views.length;
      bcActiveView = views[idx];
    }
  }

  // Barang Certificates: form state
  let bcForm: { name: string; no_seri: string; mitra_id: number | '' | null } = {
    name: '',
    no_seri: '',
    mitra_id: ''
  };

  async function fetchMitraDetails() {
    loadingMitra = true;
    errorMitra = '';
    mitraId = $page.params.id;
    if (!mitraId) {
      errorMitra = 'Mitra ID tidak ditemukan.';
      loadingMitra = false;
      return;
    }
    try {
      const response = await axiosClient.get(`/mitras/${mitraId}`);
      mitra = response.data.data;
      form = { ...mitra };
      if (response.data.form_dependencies?.kategori_options) {
        mitraKategoriOptions = response.data.form_dependencies.kategori_options;
      }
    } catch (err: any) {
      errorMitra = err.response?.data?.message || 'Gagal memuat detail mitra.';
      console.error('Error fetching mitra details:', err.response || err);
    } finally {
      loadingMitra = false;
    }
  }

  onMount(() => {
    mitraId = $page.params.id;
    fetchMitraDetails();
    fetchBarangCertificates();
    bcInitialized = true;
  });

  function openEditModal() {
    if (!canUpdateMitra) {
      console.warn('User lacks mitra-update permission');
      return;
    }
    showEditModal = true;
  }

  async function handleSubmitUpdate() {
    if (!mitra?.id) return;
    if (!canUpdateMitra) {
      console.warn('User lacks mitra-update permission');
      return;
    }
    try {
      await axiosClient.put(`/mitras/${mitra.id}`, form);
      alert('Mitra berhasil diperbarui!');
      goto(`/mitras/${mitra.id}`);
      showEditModal = false;
      fetchMitraDetails();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal memperbarui mitra.';
      alert('Error:\n' + messages);
      console.error('Update mitra failed:', err.response || err);
    }
  }

  async function handleDelete() {
    if (!mitra?.id) return;
    if (!canDeleteMitra) {
      console.warn('Delete mitra blocked by permission');
      return;
    }
    if (confirm('Apakah Anda yakin ingin menghapus mitra ini?')) {
      try {
        await axiosClient.delete(`/mitras/${mitra.id}`);
        alert('Mitra berhasil dihapus!');
        goto('/mitras');
      } catch (err: any) {
        alert('Gagal menghapus mitra: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
        console.error('Delete mitra failed:', err.response || err);
      }
    }
  }

  // Helper untuk badge
  function getKategoriBadgeColor(kategori: string) {
    // badge konsisten: tint di light, deep di dark
    const base =
      'inline-flex rounded-full px-2 text-xs font-semibold leading-5';
    switch (kategori) {
      case 'Pribadi':
      case 'Perusahaan':
      case 'Customer':
      case 'Vendor':
        return `${base} bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200`;
      default:
        return `${base} bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200`;
    }
  }

  // ===== Barang Certificates handlers =====
  async function fetchBarangCertificates() {
    if (!mitraId) return;
    bcLoading = true;
    bcError = '';
    try {
      const res = await axiosClient.get('/barang-certificates', {
        params: { search: bcSearch, mitra_id: mitraId, page: bcCurrentPage, per_page: perPage, sort_by: bcSortBy, sort_dir: bcSortDir }
      });
      bcItems = res.data?.data ?? [];
      bcCurrentPage = res.data?.meta?.current_page ?? res.data?.pagination?.current_page ?? res.data?.current_page ?? 1;
      bcLastPage = res.data?.meta?.last_page ?? res.data?.pagination?.last_page ?? res.data?.last_page ?? 1;
      bcTotalItems = res.data?.meta?.total ?? res.data?.pagination?.total ?? res.data?.total ?? bcItems.length;
    } catch (err: any) {
      bcError = err?.response?.data?.message || 'Gagal memuat data.';
      console.error('Error fetching barang certificates:', err?.response || err);
    } finally {
      bcLoading = false;
    }
  }

  function bcHandleSearchChange() { bcCurrentPage = 1; fetchBarangCertificates(); }
  let searchTimer: ReturnType<typeof setTimeout>;

  function bcHandleSearchDebounced() {
    clearTimeout(searchTimer);
    
    searchTimer = setTimeout(() => {
      bcHandleSearchChange();
    }, 800);
  }
  function bcGoToPage(page: number) { if (page > 0 && page <= bcLastPage) { bcCurrentPage = page; fetchBarangCertificates(); } }
  function bcOpenCreateModal() {
    if (!canCreateBarangCert) {
      console.warn('User lacks bc-create permission');
      return;
    }
    if (!mitra?.id) return;
    bcForm = { name: '', no_seri: '', mitra_id: mitra.id };
    bcShowCreateModal = true;
  }
  function bcOpenEditModal(item: BarangCertificate) {
    if (!canUpdateBarangCert) {
      console.warn('User lacks bc-update permission');
      return;
    }
    bcEditingItem = { ...item };
    bcForm = { name: item.name ?? '', no_seri: item.no_seri ?? '', mitra_id: mitra?.id ?? '' };
    bcShowEditModal = true;
  }
  function bcOpenDetailDrawer(item: BarangCertificate) { bcSelectedItem = { ...item }; bcShowDetailDrawer = true; }

  async function bcHandleSubmitCreate() {
    if (!canCreateBarangCert) {
      console.warn('Create barang certificate blocked by permission');
      return;
    }
    try {
      if (mitra?.id) bcForm.mitra_id = mitra.id;
      await axiosClient.post('/barang-certificates', bcForm);
      alert('Data berhasil ditambahkan');
      bcShowCreateModal = false;
      fetchBarangCertificates();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal menambahkan data.';
      alert('Error:\n' + messages);
      console.error('Create failed:', err.response || err);
    }
  }

  async function bcHandleSubmitUpdate() {
    if (!canUpdateBarangCert) {
      console.warn('Update barang certificate blocked by permission');
      return;
    }
    if (!bcEditingItem?.id) return;
    try {
      if (mitra?.id) bcForm.mitra_id = mitra.id;
      await axiosClient.put(`/barang-certificates/${bcEditingItem.id}`, bcForm);
      alert('Data berhasil diperbarui');
      bcShowEditModal = false;
      fetchBarangCertificates();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal memperbarui data.';
      alert('Error:\n' + messages);
      console.error('Update failed:', err.response || err);
    }
  }

  async function bcHandleDelete(id: number) {
    if (!canDeleteBarangCert) {
      console.warn('Delete barang certificate blocked by permission');
      return;
    }
    if (!confirm('Yakin ingin menghapus data ini?')) return;
    try {
      await axiosClient.delete(`/barang-certificates/${id}`);
      alert('Data berhasil dihapus');
      fetchBarangCertificates();
    } catch (err: any) {
      alert('Gagal menghapus data: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
      console.error('Delete failed:', err.response || err);
    }
  }

  $: if (activeTab === 'barang' && !bcInitialized) {
    bcInitialized = true;
    fetchBarangCertificates();
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
  <title>Detail Mitra - Indogreen</title>
</svelte:head>

{#if loadingMitra}
  <p class="text-gray-900 dark:text-white">Memuat detail mitra...</p>
{:else if errorMitra}
  <p class="text-red-500">{errorMitra}</p>
{:else if mitra}
  <div class="max-w-1xl mx-auto mb-8">
    <div class="flex justify-between items-center mb-4">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-2xl">
          {mitra.nama}
        </h2>
        <div class="my-2 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
          <div class="my-2 flex items-center text-sm text-gray-500 dark:text-gray-300">
            {#if mitra.is_pribadi}<span class="{getKategoriBadgeColor('Pribadi')}">Pribadi</span>{/if}
            {#if mitra.is_perusahaan}<span class="{getKategoriBadgeColor('Perusahaan')}">Perusahaan</span>{/if}
            {#if mitra.is_customer}<span class="{getKategoriBadgeColor('Customer')}">Customer</span>{/if}
            {#if mitra.is_vendor}<span class="{getKategoriBadgeColor('Vendor')}">Vendor</span>{/if}
          </div>
        </div>
      </div>
      <div class="flex flex-col md:flex-row mt-2 mb-4 md:mt-0 md:ml-4 md:mb-4 space-y-2 md:space-y-0 md:space-x-4">
        {#if canUpdateMitra}
          <button
            on:click={openEditModal}
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white
                  bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                  dark:focus:ring-offset-gray-800"
          >
            Edit Mitra
          </button>
        {/if}
        {#if canDeleteMitra}
          <button
            on:click={handleDelete}
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white
                  bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500
                  dark:focus:ring-offset-gray-800"
          >
            Hapus Mitra
          </button>
        {/if}
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex items-center justify-between mb-4">
      <div class="p-1 bg-gray-200 dark:bg-gray-700 rounded-lg inline-flex" role="tablist">
        <button
          on:click={() => (activeTab = 'detail')}
          class="px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-200 text-gray-700 dark:text-gray-200"
          class:bg-white={activeTab === 'detail'}
          class:dark:bg-neutral-900={activeTab === 'detail'}
          class:shadow={activeTab === 'detail'}
          role="tab"
          aria-selected={activeTab === 'detail'}
        >
          Detail
        </button>
        <button
          on:click={() => (activeTab = 'barang')}
          class="px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-200 text-gray-700 dark:text-gray-200"
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

    <!-- DETAIL -->
    {#if activeTab === 'detail'}
      <div class="bg-white dark:bg-black shadow overflow-hidden">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Informasi Mitra</h3>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700">
          <MitraDetail mitra={mitra} />
        </div>
      </div>
    {/if}

    <!-- BARANG CERTIFICATES -->
    {#if activeTab === 'barang'}
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row items-center justify-between mb-4 space-y-4 sm:space-y-0 sm:space-x-4">
          <div class="flex w-full sm:w-auto space-x-2">
            <select
              bind:value={bcSortDir}
              on:change={bcHandleSearchChange}
              class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300
                    dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"
              aria-label="Sortir create"
              title="Sortir create"
            >
              <option value="desc">Sortir: Create Terbaru</option>
              <option value="asc">Sortir: Create Terlama</option>
            </select>
          </div>
          <div class="w-full sm:w-auto flex-grow">
            <div class="relative w-full sm:w-auto">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
              </div>
              <input
                type="text"
                placeholder="Cari barang certificate..."
                bind:value={bcSearch}
                on:input={bcHandleSearchDebounced}
                class="block w-full pl-10 pr-3 py-2 border rounded-md leading-5 bg-white text-gray-900 placeholder-gray-500 border-gray-300
                       focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm
                       dark:bg-neutral-900 dark:text-gray-100 dark:placeholder-gray-400 dark:border-gray-700"
              />
            </div>
          </div>
          <div class="flex space-x-2 w-full sm:w-auto">
            {#if canCreateBarangCert}
              <button
                on:click={bcOpenCreateModal}
                class="px-4 py-2 w-full sm:w-auto border border-transparent text-sm font-medium rounded-md shadow-sm text-white
                      bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                      dark:focus:ring-offset-gray-800"
              >
                Tambah Barang
              </button>
            {/if}
          </div>
        </div>

        <div class="flex items-center justify-between mb-4">
          <!-- Segmented icon toggle (Table / List) -->
          <div
            class="bg-white dark:bg-neutral-900 rounded-md inline-flex gap-1"
            role="tablist"
            aria-label="Switch view"
            tabindex="0"
            on:keydown={handleViewKeydown}
          >
            <!-- TABLE -->
            <button
              on:click={() => (bcActiveView = 'table')}
              class="grid h-9 w-9 place-items-center rounded-md
                    text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-50
                    focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:focus-visible:ring-gray-500"
              class:bg-white={bcActiveView === 'table'}
              class:dark:bg-neutral-900={bcActiveView === 'table'}
              class:text-gray-900={bcActiveView === 'table'}
              class:dark:text-white={bcActiveView === 'table'}
              class:border={bcActiveView === 'table'}
              class:border-gray-300={bcActiveView === 'table'}
              class:dark:border-gray-700={bcActiveView === 'table'}
              role="tab"
              aria-selected={bcActiveView === 'table'}
              aria-label="Table view"
              title="Table"
            >
              <!-- Table icon -->
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <rect x="3.5" y="4.5" width="17" height="15" rx="2"></rect>
                <line x1="3.5" y1="9"  x2="20.5" y2="9"></line>
                <line x1="3.5" y1="13" x2="20.5" y2="13"></line>
                <line x1="3.5" y1="17" x2="20.5" y2="17"></line>
              </svg>
              <span class="sr-only">Tampilan Tabel</span>
            </button>

            <!-- LIST -->
            <button
              on:click={() => (bcActiveView = 'list')}
              class="grid h-9 w-9 place-items-center rounded-md
                    text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-50
                    focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:focus-visible:ring-gray-500"
              class:bg-white={bcActiveView === 'list'}
              class:dark:bg-neutral-900={bcActiveView === 'list'}
              class:text-gray-900={bcActiveView === 'list'}
              class:dark:text-white={bcActiveView === 'list'}
              class:border={bcActiveView === 'list'}
              class:border-gray-300={bcActiveView === 'list'}
              class:dark:border-gray-700={bcActiveView === 'list'}
              role="tab"
              aria-selected={bcActiveView === 'list'}
              aria-label="List view"
              title="List"
            >
              <!-- List icon -->
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <circle cx="5" cy="6" r="1.3"></circle>
                <circle cx="5" cy="12" r="1.3"></circle>
                <circle cx="5" cy="18" r="1.3"></circle>
                <line x1="9" y1="6"  x2="20" y2="6"></line>
                <line x1="9" y1="12" x2="20" y2="12"></line>
                <line x1="9" y1="18" x2="20" y2="18"></line>
              </svg>
              <span class="sr-only">Tampilan List</span>
            </button>
          </div>
        </div>

        {#if bcLoading}
          <p class="text-gray-900 dark:text-white">Memuat data...</p>
        {:else if bcError}
          <p class="text-red-500">{bcError}</p>
        {:else if bcItems.length === 0}
          <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
              <li class="px-4 py-4 sm:px-6">
                <p class="text-sm text-gray-500 dark:text-gray-300">Belum ada data.</p>
              </li>
            </ul>
          </div>
        {:else}
          {#if bcActiveView === 'list'}
            <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
              <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                {#each bcItems as item (item.id)}
                  <li>
                    <a 
                      href={`/barang-certificates/${item.id}`}
                      class="block hover:bg-gray-50 dark:hover:bg-neutral-950 px-4 py-4 sm:px-6"
                      on:click|preventDefault={() => bcOpenDetailDrawer(item)}
                      on:keydown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                          e.preventDefault();
                          bcOpenDetailDrawer(item);
                        }
                      }}
                      role="button"
                      aria-label={`Lihat detail barang certificate ${item.name}`}
                    >
                      <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">{item.name}</p>
                      </div>
                      <div class="mt-2 sm:flex sm:justify-between">
                        <div class="sm:flex">
                          <p class="flex items-center text-sm text-gray-500 dark:text-gray-300">No. Seri: {item.no_seri} | Mitra: {mitra.nama}</p>
                        </div>
                      </div>
                    </a>
                    <div class="flex justify-end px-4 py-2 sm:px-6 space-x-2">
                      <button on:click|stopPropagation={() => bcOpenDetailDrawer(item)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-yellow-600 hover:bg-yellow-700">Detail</button>
                      {#if canUpdateBarangCert}
                        <button on:click|stopPropagation={() => bcOpenEditModal(item)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700">Edit</button>
                      {/if}
                      {#if canDeleteBarangCert}
                        <button on:click|stopPropagation={() => bcHandleDelete(item.id)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-red-600 hover:bg-red-700">Hapus</button>
                      {/if}
                    </div>
                  </li>
                {/each}
              </ul>
              {#if bcItems.length > 0}
                <Pagination
                  currentPage={bcCurrentPage}
                  lastPage={bcLastPage}
                  onPageChange={bcGoToPage}
                  totalItems={bcTotalItems}
                  itemsPerPage={perPage}
                  perPageOptions={perPageOptions}
                  onPerPageChange={(n) => { perPage = n; bcCurrentPage = 1; fetchBarangCertificates(); }}
                />
              {/if}
            </div>
          {/if}

          {#if bcActiveView === 'table'}
            <div class="mt-4 bg-white dark:bg-black shadow-md rounded-lg">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                  <thead class="bg-gray-50 dark:bg-neutral-900">
                    <tr>
                      <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Nama Barang</th>
                      <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">No. Seri</th>
                      <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Mitra</th>
                      <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-black">
                    {#each bcItems as item (item.id)}
                      <tr>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                          <a 
                            href={`/barang-certificates/${item.id}`}
                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                            on:click|preventDefault={() => bcOpenDetailDrawer(item)}
                            on:keydown={(e) => {
                              if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                bcOpenDetailDrawer(item);
                              }
                            }}
                            role="button"
                            aria-label={`Lihat detail barang certificate ${item.name}`}
                          >
                            {item.name}
                          </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{item.no_seri}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{mitra.nama}</td>
                        <td class="relative whitespace-nowrap px-3 py-4 text-sm">
                          <div class="flex items-center space-x-2">
                            <button on:click={() => bcOpenDetailDrawer(item)} title="Detail" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300">
                              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                              <span class="sr-only">Detail, {item.name}</span>
                            </button>
                            {#if canUpdateBarangCert}
                              <button on:click={() => bcOpenEditModal(item)} title="Edit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span class="sr-only">Edit, {item.name}</span>
                              </button>
                            {/if}
                            {#if canDeleteBarangCert}
                              <button on:click={() => bcHandleDelete(item.id)} title="Hapus" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                <span class="sr-only">Hapus, {item.name}</span>
                              </button>
                            {/if}
                          </div>
                        </td>
                      </tr>
                    {/each}
                  </tbody>
                </table>
              </div>
              {#if bcItems.length > 0}
                <Pagination
                  currentPage={bcCurrentPage}
                  lastPage={bcLastPage}
                  onPageChange={bcGoToPage}
                  totalItems={bcTotalItems}
                  itemsPerPage={perPage}
                  perPageOptions={perPageOptions}
                  onPerPageChange={(n) => { perPage = n; bcCurrentPage = 1; fetchBarangCertificates(); }}
                />
              {/if}
            </div>
          {/if}
        {/if}
      </div>
    {/if}
  </div>
{/if}

  {#if mitra}
    <MitraFormModal
      bind:show={showEditModal}
      title="Edit Mitra"
      submitLabel="Update Mitra"
      idPrefix="edit_mitra"
      {form}
      onSubmit={handleSubmitUpdate}
    />
  {/if}

  <!-- Barang Certificates: Create Modal -->
  <BarangCertificateFormModal
    bind:show={bcShowCreateModal}
    title="Tambah Barang Certificate"
    submitLabel="Simpan"
    idPrefix="bc_create"
    form={bcForm}
    showMitra={false}
    onSubmit={bcHandleSubmitCreate}
  />

  <!-- Barang Certificates: Edit Modal -->
  <BarangCertificateFormModal
    bind:show={bcShowEditModal}
    title="Edit Barang Certificate"
    submitLabel="Update"
    idPrefix="bc_edit"
    form={bcForm}
    showMitra={false}
    onSubmit={bcHandleSubmitUpdate}
  />

  <!-- Barang Certificates Detail Drawer -->
  <Drawer 
    bind:show={bcShowDetailDrawer} 
    title="Detail Barang Certificate"
    on:close={() => bcShowDetailDrawer = false}
  >
    <BarangCertificatesDetail barangCertificates={bcSelectedItem} />
  </Drawer>
