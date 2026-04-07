<script lang="ts">
  import { onMount } from 'svelte';
  import axiosClient from '$lib/axiosClient';
  import Pagination from '$lib/components/Pagination.svelte';
  import Drawer from '$lib/components/Drawer.svelte';
  import BarangCertificatesDetail from '$lib/components/detail/BarangCertificatesDetail.svelte';
  import BarangCertificateFormModal from '$lib/components/form/BarangCertificateFormModal.svelte';
  import { userPermissions } from '$lib/stores/permissions';

  type Mitra = { id: number; nama: string };
  type BarangCertificate = {
    id: number;
    name: string;
    no_seri: string;
    mitra_id: number | '' | null;
    mitra?: { id: number; nama: string } | null;
    created_at?: string;
    updated_at?: string;
  };

  let items: BarangCertificate[] = [];
  let mitras: Mitra[] = [];
  let loading = true;
  let error = '';
  let search = '';
  let mitraFilter: number | '' = '';

  let sortBy: 'created' = 'created';
  let sortDir: 'asc' | 'desc' = 'desc';

  let currentPage = 1;
  let lastPage = 1;
  let totalItems = 0;
  let perPage: number = 50;
  const perPageOptions = [10, 25, 50, 100];

  // Permissions
  let canCreateBarangCert = false;
  let canUpdateBarangCert = false;
  let canDeleteBarangCert = false;

  $: {
    const perms = $userPermissions ?? [];
    canCreateBarangCert = perms.includes('bc-create');
    canUpdateBarangCert = perms.includes('bc-update');
    canDeleteBarangCert = perms.includes('bc-delete');
  }

  // Modal state
  let showCreateModal = false;
  let showEditModal = false;
  let editingItem: BarangCertificate | null = null;

  // Drawer state
  let showDetailDrawer = false;
  let selectedItem: BarangCertificate | null = null;

  // View state
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

  // Form state
  let form: {
    name: string;
    no_seri: string;
    mitra_id: number | '' | null;
  } = {
    name: '',
    no_seri: '',
    mitra_id: ''
  };

  async function fetchList() {
    loading = true;
    error = '';
    try {
      const res = await axiosClient.get('/barang-certificates', {
        params: {
          search,
          mitra_id: mitraFilter || undefined,
          sort_by: sortBy,
          sort_dir: sortDir,
          page: currentPage,
          per_page: perPage
        }
      });
      items = res.data?.data ?? [];
      mitras = res.data?.form_dependencies?.mitras ?? mitras;
      currentPage = res.data?.meta?.current_page ?? res.data?.pagination?.current_page ?? res.data?.current_page ?? 1;
      lastPage = res.data?.meta?.last_page ?? res.data?.pagination?.last_page ?? res.data?.last_page ?? 1;
      totalItems = res.data?.meta?.total ?? res.data?.pagination?.total ?? res.data?.total ?? items.length;
    } catch (err: any) {
      error = err?.response?.data?.message || 'Gagal memuat data.';
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    fetchList();
  });

  function handleFilterOrSearch() {
    currentPage = 1;
    fetchList();
  }

  let searchTimer: ReturnType<typeof setTimeout>;

  function handleSearchDebounced() {
    clearTimeout(searchTimer);
    
    searchTimer = setTimeout(() => {
      handleFilterOrSearch();
    }, 800);
  }

  function goToPage(page: number) {
    if (page > 0 && page <= lastPage) {
      currentPage = page;
      fetchList();
    }
  }

  function openCreateModal() {
    if (!canCreateBarangCert) {
      console.warn('User lacks bc-create permission');
      return;
    }
    form = { name: '', no_seri: '', mitra_id: '' };
    showCreateModal = true;
  }

  function openEditModal(item: BarangCertificate) {
    if (!canUpdateBarangCert) {
      console.warn('User lacks bc-update permission');
      return;
    }
    editingItem = { ...item };
    form = {
      name: item.name ?? '',
      no_seri: item.no_seri ?? '',
      mitra_id: item.mitra_id ?? ''
    };
    showEditModal = true;
  }

  function openDetailDrawer(item: BarangCertificate) {
    selectedItem = { ...item };
    showDetailDrawer = true;
  }

  async function handleSubmitCreate() {
    if (!canCreateBarangCert) {
      console.warn('Create barang certificate blocked by permission');
      return;
    }
    try {
      await axiosClient.post('/barang-certificates', form);
      alert('Data berhasil ditambahkan');
      showCreateModal = false;
      fetchList();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal menambahkan data.';
      alert('Error:\n' + messages);
      console.error('Create failed:', err.response || err);
    }
  }

  async function handleSubmitUpdate() {
    if (!canUpdateBarangCert) {
      console.warn('Update barang certificate blocked by permission');
      return;
    }
    if (!editingItem?.id) return;
    try {
      await axiosClient.put(`/barang-certificates/${editingItem.id}`, form);
      alert('Data berhasil diperbarui');
      showEditModal = false;
      fetchList();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal memperbarui data.';
      alert('Error:\n' + messages);
      console.error('Update failed:', err.response || err);
    }
  }

  async function handleDelete(id: number) {
    if (!canDeleteBarangCert) {
      console.warn('Delete barang certificate blocked by permission');
      return;
    }
    if (!confirm('Yakin ingin menghapus data ini?')) return;
    try {
      await axiosClient.delete(`/barang-certificates/${id}`);
      alert('Data berhasil dihapus');
      fetchList();
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
  $: lockBodyScroll(showDetailDrawer || showCreateModal || showEditModal);
</script>

<svelte:head>
  <title>Daftar Barang Sertifikat - Indogreen</title>
</svelte:head>

<div class="flex flex-col sm:flex-row items-center justify-between mb-4 space-y-4 sm:space-y-0 sm:space-x-4">
  <div class="flex w-full sm:w-auto space-x-2">
    <select
      bind:value={sortDir}
      on:change={handleFilterOrSearch}
      class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300
             dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"
      aria-label="Sortir create"
      title="Sortir create"
    >
      <option value="desc">Create: Terbaru</option>
      <option value="asc">Create: Terlama</option>
    </select>

    <select
      bind:value={mitraFilter}
      on:change={handleFilterOrSearch}
      class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300
             dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700">
      <option value="">Mitra: Semua</option>
      {#each mitras as m}
        <option value={m.id}>{m.nama}</option>
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
        placeholder="Cari barang certificate..."
        bind:value={search}
        on:input={handleSearchDebounced}
        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white text-gray-900 placeholder-gray-500
               focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm
               dark:bg-neutral-900 dark:text-gray-100 dark:placeholder-gray-400 dark:border-gray-700"
      />
    </div>
  </div>
  <div class="flex space-x-2 w-full sm:w-auto">
    {#if canCreateBarangCert}
      <button
        on:click={openCreateModal}
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
  <p class="text-gray-900 dark:text-white">Memuat data...</p>
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if items.length === 0}
  <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
      <li class="px-4 py-4 sm:px-6">
        <p class="text-sm text-gray-500 dark:text-gray-300">Belum ada data.</p>
      </li>
    </ul>
  </div>
{:else}
  {#if activeView === 'list'}
    <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
      <ul class="divide-y divide-gray-200 dark:divide-gray-700">
        {#each items as item (item.id)}
          <li>
            <a href={`/barang-certificates/${item.id}`} class="block hover:bg-gray-50 dark:hover:bg-neutral-950 px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">{item.name}</p>
              </div>
              <div class="mt-2 sm:flex sm:justify-between">
                <div class="sm:flex">
                  <p class="flex items-center text-sm text-gray-500 dark:text-gray-300">
                    No. Seri: {item.no_seri} | Mitra: {item.mitra?.nama || '-'}
                  </p>
                </div>
              </div>
            </a>
            <div class="flex justify-end px-4 py-2 sm:px-6 space-x-2">
              <button on:click|stopPropagation={() => openDetailDrawer(item)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-yellow-600 hover:bg-yellow-700">Detail</button>
              {#if canUpdateBarangCert}
                <button on:click|stopPropagation={() => openEditModal(item)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700">Edit</button>
              {/if}
              {#if canDeleteBarangCert}
                <button on:click|stopPropagation={() => handleDelete(item.id)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-red-600 hover:bg-red-700">Hapus</button>
              {/if}
            </div>
          </li>
        {/each}
      </ul>
      {#if items.length > 0}
        <Pagination currentPage={currentPage} lastPage={lastPage} onPageChange={goToPage} totalItems={totalItems} itemsPerPage={perPage} perPageOptions={perPageOptions} onPerPageChange={(n) => { perPage = n; currentPage = 1; fetchList(); }} />
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
                Nama Barang
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                No. Seri
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                Mitra
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-black">
            {#each items as item (item.id)}
              <tr>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                  <a href={`/barang-certificates/${item.id}`} class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                    {item.name}
                  </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {item.no_seri}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {item.mitra?.nama || '-'}
                </td>
                <td class="relative whitespace-nowrap px-3 py-4 text-sm">
                  <div class="flex items-center space-x-2">
                    <button on:click={() => openDetailDrawer(item)} title="Detail" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      <span class="sr-only">Detail, {item.name}</span>
                    </button>
                    {#if canUpdateBarangCert}
                      <button on:click={() => openEditModal(item)} title="Edit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        <span class="sr-only">Edit, {item.name}</span>
                      </button>
                    {/if}
                    {#if canDeleteBarangCert}
                      <button on:click={() => handleDelete(item.id)} title="Hapus" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
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
      {#if items.length > 0}
        <Pagination currentPage={currentPage} lastPage={lastPage} onPageChange={goToPage} totalItems={totalItems} itemsPerPage={perPage} perPageOptions={perPageOptions} onPerPageChange={(n) => { perPage = n; currentPage = 1; fetchList(); }} />
      {/if}
    </div>
  {/if}
{/if}

<BarangCertificateFormModal
  bind:show={showCreateModal}
  title="Tambah Barang Certificate"
  submitLabel="Simpan"
  idPrefix="create"
  {form}
  {mitras}
  showMitra={true}
  onSubmit={handleSubmitCreate}
/>

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

<Drawer 
  bind:show={showDetailDrawer} 
  title="Detail Barang Certificate"
  on:close={() => showDetailDrawer = false}
>
  <BarangCertificatesDetail barangCertificates={selectedItem} />
</Drawer>
