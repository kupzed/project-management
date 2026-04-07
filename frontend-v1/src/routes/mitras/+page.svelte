<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import axiosClient from '$lib/axiosClient';
  import Drawer from '$lib/components/Drawer.svelte';
  import MitraDetail from '$lib/components/detail/MitraDetail.svelte'; 
  import Pagination from '$lib/components/Pagination.svelte';
  import MitraFormModal from '$lib/components/form/MitraFormModal.svelte';
  import { userPermissions } from '$lib/stores/permissions';

  let mitras: any[] = [];
  let loading = true;
  let error = '';
  let search: string = '';
  let kategoriFilter: string = ''; // 'pribadi', 'perusahaan', 'customer', 'vendor'
  let dateFromFilter: string = '';
  let dateToFilter: string = '';
  let showDateFilter: boolean = false;

  let sortBy: 'created' = 'created';
  let sortDir: 'asc' | 'desc' = 'desc';

  let currentPage: number = 1;
  let lastPage: number = 1;
  let totalMitras: number = 0;
  let perPage: number = 50;
  const perPageOptions = [10, 25, 50, 100];

  let activeView: 'table' | 'list' = 'table';
  const views: Array<'table' | 'list'> = ['table', 'list'];

  function handleViewKeydown(e: KeyboardEvent) {
    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
      e.preventDefault();
      let idx = views.indexOf(activeView);
      idx = e.key === 'ArrowRight' ? (idx + 1) % views.length : (idx - 1 + views.length) % views.length;
      activeView = views[idx];
    }
  }

  let showCreateModal: boolean = false;
  let showEditModal: boolean = false;
  let editingMitra: any = null; 
  let showDetailDrawer: boolean = false;
  let selectedMitra: any = null;

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

  let mitraKategoriOptions: Array<{value: string, label: string}> = [];

  // Permissions
  let canCreateMitra = false;
  let canUpdateMitra = false;
  let canDeleteMitra = false;

  $: {
    const perms = $userPermissions ?? [];
    canCreateMitra = perms.includes('mitra-create');
    canUpdateMitra = perms.includes('mitra-update');
    canDeleteMitra = perms.includes('mitra-delete');
  }

  async function fetchMitras() {
    loading = true;
    error = '';
    try {
      const response = await axiosClient.get('/mitras', {
        params: {
          search: search,
          kategori: kategoriFilter,
          date_from: dateFromFilter,
          date_to: dateToFilter,
          sort_by: sortBy,
          sort_dir: sortDir,
          page: currentPage,
          per_page: perPage,
        }
      });
      mitras = response.data.data;
      
      const meta = response.data.meta || response.data.pagination || response.data;
      currentPage = meta.current_page ?? 1;
      lastPage = meta.last_page ?? 1;
      totalMitras = meta.total ?? 0;

      if (response.data.form_dependencies?.kategori_options) {
        mitraKategoriOptions = response.data.form_dependencies.kategori_options;
      }
    } catch (err: any) {
      error = err.response?.data?.message || 'Gagal memuat mitra.';
      console.error('Error fetching mitras:', err);
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    fetchMitras();
    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
  });

  function handleFilterOrSearch() {
    currentPage = 1;
    fetchMitras();
  }

  let searchTimer: ReturnType<typeof setTimeout>;

  function handleSearchDebounced() {
    clearTimeout(searchTimer);
    
    searchTimer = setTimeout(() => {
      handleFilterOrSearch();
    }, 800);
  }

  function clearFilters() {
    search = '';
    kategoriFilter = '';
    dateFromFilter = '';
    dateToFilter = '';
    showDateFilter = false;
    sortBy = 'created';
    sortDir = 'desc';
    currentPage = 1;
    fetchMitras();
  }

  function toggleDateFilter() {
    showDateFilter = !showDateFilter;
  }

  function handleClickOutside(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest('.date-filter-dropdown') && !target.closest('.date-filter-button')) {
      showDateFilter = false;
    }
  }

  function goToPage(page: number) {
    if (page > 0 && page <= lastPage) {
      currentPage = page;
      fetchMitras();
    }
  }

  function openCreateModal() {
    if (!canCreateMitra) {
      console.warn('User lacks mitra-create permission');
      return;
    }
    form = {
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
    showCreateModal = true;
  }

  function openEditModal(mitra: any) {
    if (!canUpdateMitra) {
      console.warn('User lacks mitra-update permission');
      return;
    }
    editingMitra = { ...mitra };
    form = { ...editingMitra };
    showEditModal = true;
  }

  function openDetailDrawer(mitra: any) {
    selectedMitra = { ...mitra };
    showDetailDrawer = true;
  }

  async function handleSubmitCreate() {
    if (!canCreateMitra) {
      console.warn('Create mitra blocked by permission');
      return;
    }
    try {
      await axiosClient.post('/mitras', form);
      alert('Mitra berhasil ditambahkan!');
      goto(`/mitras`);
      showCreateModal = false;
      fetchMitras();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal menambahkan mitra.';
      alert('Error:\n' + messages);
      console.error('Create mitra failed:', err.response || err);
    }
  }

  async function handleSubmitUpdate() {
    if (!canUpdateMitra) {
      console.warn('Update mitra blocked by permission');
      return;
    }
    if (!editingMitra?.id) return;
    try {
      await axiosClient.put(`/mitras/${editingMitra.id}`, form);
      alert('Mitra berhasil diperbarui!');
      goto(`/mitras`);
      showEditModal = false;
      fetchMitras();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal memperbarui mitra.';
      alert('Error:\n' + messages);
      console.error('Update mitra failed:', err.response || err);
    }
  }

  async function handleDelete(mitraId: number) {
    if (!canDeleteMitra) {
      console.warn('Delete mitra blocked by permission');
      return;
    }
    if (confirm('Apakah Anda yakin ingin menghapus mitra ini?')) {
      try {
        await axiosClient.delete(`/mitras/${mitraId}`);
        alert('Mitra berhasil dihapus!');
        goto(`/mitras`);
        fetchMitras();
      } catch (err: any) {
        alert('Gagal menghapus mitra: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
        console.error('Delete mitra failed:', err.response || err);
      }
    }
  }

  function getKategoriBadgeColor(kategori: string) {
    const base = 'inline-flex rounded-full px-2 text-xs font-semibold leading-5';
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
  $: lockBodyScroll(showDetailDrawer || showCreateModal || showEditModal);
</script>

<svelte:head>
  <title>Daftar Mitra - Indogreen</title>
</svelte:head>

<div class="flex flex-col sm:flex-row items-center justify-between mb-4 space-y-4 sm:space-y-0 sm:space-x-4">
  <div class="flex w-full sm:w-auto space-x-2">
    <select
      bind:value={sortDir}
      on:change={handleFilterOrSearch}
      class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300
             dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"
    >
      <option value="desc">Create: Terbaru</option>
      <option value="asc">Create: Terlama</option>
    </select>

    <select
      bind:value={kategoriFilter}
      on:change={handleFilterOrSearch}
      class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300
             dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"
    >
      <option value="">Kategori: Semua</option>
      {#each mitraKategoriOptions as kategori (kategori.value)}
        <option value={kategori.value}>{kategori.label}</option>
      {/each}
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
        placeholder="Cari mitra..."
        bind:value={search}
        on:input={handleSearchDebounced}
        class="block w-full pl-10 pr-3 py-2 border rounded-md leading-5 bg-white text-gray-900 placeholder-gray-500 border-gray-300
               focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm
               dark:bg-neutral-900 dark:text-gray-100 dark:placeholder-gray-400 dark:border-gray-700"
      />
    </div>
  </div>

  {#if canCreateMitra}
    <button
      on:click={openCreateModal}
      class="px-4 py-2 w-full sm:w-auto border border-transparent text-sm font-medium rounded-md shadow-sm text-white
             bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
             dark:focus:ring-offset-gray-800"
    >
      Tambah Mitra
    </button>
  {/if}
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
      on:click={() => (activeView = 'table')}
      class="grid h-9 w-9 place-items-center rounded-md
             text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-50
             focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:focus-visible:ring-gray-500"
      class:bg-white={activeView === 'table'}
      class:dark:bg-neutral-900={activeView === 'table'}
      class:text-gray-900={activeView === 'table'}
      class:dark:text-white={activeView === 'table'}
      class:border={activeView === 'table'}
      class:border-gray-300={activeView === 'table'}
      class:dark:border-gray-700={activeView === 'table'}
      role="tab"
      aria-selected={activeView === 'table'}
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
      on:click={() => (activeView = 'list')}
      class="grid h-9 w-9 place-items-center rounded-md
             text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-50
             focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:focus-visible:ring-gray-500"
      class:bg-white={activeView === 'list'}
      class:dark:bg-neutral-900={activeView === 'list'}
      class:text-gray-900={activeView === 'list'}
      class:dark:text-white={activeView === 'list'}
      class:border={activeView === 'list'}
      class:border-gray-300={activeView === 'list'}
      class:dark:border-gray-700={activeView === 'list'}
      role="tab"
      aria-selected={activeView === 'list'}
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

{#if loading}
  <p class="text-gray-900 dark:text-white">Memuat mitra...</p>
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if mitras.length === 0}
  <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
      <li class="px-4 py-4 sm:px-6">
        <p class="text-sm text-gray-500 dark:text-gray-300">Belum ada mitra. </p>
      </li>
    </ul>
  </div>
{:else}
  {#if activeView === 'list'}
    <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
      <ul class="divide-y divide-gray-200 dark:divide-gray-700">
        {#each mitras as mitra (mitra.id)}
          <li>
            <a href={`/mitras/${mitra.id}`} class="block hover:bg-gray-50 dark:hover:bg-neutral-950 px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">
                  {mitra.nama}
                </p>
                <div class="ml-2 flex-shrink-0 flex">
                  {#if mitra.is_pribadi}<span class="{getKategoriBadgeColor('Pribadi')}">Pribadi</span>{/if}
                  {#if mitra.is_perusahaan}<span class="{getKategoriBadgeColor('Perusahaan')}">Perusahaan</span>{/if}
                  {#if mitra.is_customer}<span class="{getKategoriBadgeColor('Customer')}">Customer</span>{/if}
                  {#if mitra.is_vendor}<span class="{getKategoriBadgeColor('Vendor')}">Vendor</span>{/if}
                </div>
              </div>
              <div class="mt-2 sm:flex sm:justify-between">
                <div class="sm:flex">
                  <p class="flex items-center text-sm text-gray-500 dark:text-gray-300">
                    {mitra.alamat.substring(0, 100)}{mitra.alamat.length > 100 ? '...' : ''}
                  </p>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-300 sm:mt-0">
                  <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                  </svg>
                  <p>
                    Email: {mitra.email}
                  </p>
                </div>
              </div>
            </a>
            <div class="flex justify-end px-4 py-2 sm:px-6 space-x-2">
              <button 
                on:click|stopPropagation={() => openDetailDrawer(mitra)} 
                class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-yellow-600 hover:bg-yellow-700">
                Detail
              </button>
              {#if canUpdateMitra}
                <button
                  on:click|stopPropagation={() => openEditModal(mitra)}
                  class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800"
                >
                  Edit
                </button>
              {/if}
              {#if canDeleteMitra}
                <button
                  on:click|stopPropagation={() => handleDelete(mitra.id)}
                  class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800"
                >
                  Hapus
                </button>
              {/if}
            </div>
          </li>
        {/each}
      </ul>
      {#if mitras.length > 0}
        <Pagination 
          currentPage={currentPage} 
          lastPage={lastPage} 
          onPageChange={goToPage} 
          totalItems={totalMitras} 
          itemsPerPage={perPage} 
          perPageOptions={perPageOptions}
          onPerPageChange={(n) => { perPage = n; currentPage = 1; fetchMitras(); }}
        />
      {/if}
    </div>
  {/if}

  {#if activeView === 'table'}
    <div class="mt-4 bg-white dark:bg-black shadow-md rounded-lg">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-neutral-900">
            <tr>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                Nama Mitra
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                Alamat
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                Kategori
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                Kontak
              </th>
              <th scope="col" class="relative px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-black">
            {#each mitras as mitra (mitra.id)}
              <tr>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                  <a href={`/mitras/${mitra.id}`} class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300" title="Detail">
                    {mitra.nama}
                  </a>
                  <br>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{mitra.email || '(email belum ditambahkan)'}</span>
                </td>
                <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  <div class="max-w-xs truncate">
                    {mitra.alamat.substring(0, 40)}{mitra.alamat.length > 40 ? '...' : ''}
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  <div class="flex flex-wrap mt-1 gap-1">
                    {#if mitra.is_pribadi}<span class="{getKategoriBadgeColor('Pribadi')}">Pribadi</span>{/if}
                    {#if mitra.is_perusahaan}<span class="{getKategoriBadgeColor('Perusahaan')}">Perusahaan</span>{/if}
                    {#if mitra.is_customer}<span class="{getKategoriBadgeColor('Customer')}">Customer</span>{/if}
                    {#if mitra.is_vendor}<span class="{getKategoriBadgeColor('Vendor')}">Vendor</span>{/if}
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {mitra.kontak_1}
                  {#if mitra.kontak_1_nama}
                    <br><span class="text-xs text-gray-400">({mitra.kontak_1_nama})</span>
                  {:else}
                    -
                  {/if}
                </td>
                <td class="relative whitespace-nowrap px-3 py-4 text-left text-sm font-medium">
                  <div class="flex items-left space-x-2">
                    <button on:click={() => openDetailDrawer(mitra)} class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" title="Detail">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      <span class="sr-only">Detail, {mitra.nama}</span>
                    </button>
                    {#if canUpdateMitra}
                      <button on:click|stopPropagation={() => openEditModal(mitra)} title="Edit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span class="sr-only">Edit, {mitra.nama}</span>
                      </button>
                    {/if}
                    {#if canDeleteMitra}
                      <button on:click|stopPropagation={() => handleDelete(mitra.id)} title="Delete" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        <span class="sr-only">Hapus, {mitra.nama}</span>
                      </button>
                    {/if}
                  </div>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
      {#if mitras.length > 0}
        <Pagination 
          currentPage={currentPage} 
          lastPage={lastPage} 
          onPageChange={goToPage} 
          totalItems={totalMitras} 
          itemsPerPage={perPage} 
          perPageOptions={perPageOptions}
          onPerPageChange={(n) => { perPage = n; currentPage = 1; fetchMitras(); }}
        />
      {/if}
    </div>
  {/if}
{/if}

<MitraFormModal
  bind:show={showCreateModal}
  title="Tambah Mitra"
  submitLabel="Tambah Mitra"
  idPrefix="create"
  {form}
  onSubmit={handleSubmitCreate}
/>

{#if editingMitra}
  <MitraFormModal
    bind:show={showEditModal}
    title="Edit Mitra"
    submitLabel="Update Mitra"
    idPrefix="edit"
    {form}
    onSubmit={handleSubmitUpdate}
  />
{/if}

<!-- Mitra Detail Drawer -->
<Drawer 
  bind:show={showDetailDrawer} 
  title="Detail Mitra"
  on:close={() => showDetailDrawer = false}
>
  <MitraDetail mitra={selectedMitra} />
</Drawer>
