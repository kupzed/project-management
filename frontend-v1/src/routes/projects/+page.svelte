<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import axiosClient from '$lib/axiosClient';
  import Drawer from '$lib/components/Drawer.svelte';
  import ProjectDetail from '$lib/components/detail/ProjectDetail.svelte';
  import ActivityDetail from '$lib/components/detail/ActivityDetail.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import ProjectFormModal from '$lib/components/form/ProjectFormModal.svelte';
  import { userPermissions } from '$lib/stores/permissions';

  let projects: any[] = [];
  let customers: any[] = [];
  let loading = true;
  let error = '';
  let search: string = '';
  let statusFilter: string = '';
  let kategoriFilter: string = '';
  let certProjectFilter: boolean = false;
  let dateFromFilter: string = '';
  let dateToFilter: string = '';
  let showDateFilter: boolean = false;
  let sortBy: 'created' | 'start_date' = 'created';
  let sortDir: 'desc' | 'asc' = 'desc';
  let currentPage: number = 1;
  let lastPage: number = 1;
  let totalProjects: number = 0;
  let perPage: number = 50;
  const perPageOptions = [10, 25, 50, 100];

  // toggle tampilan
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

  // modal
  let showCreateModal = false;
  let showEditModal = false;
  let editingProject: any = null;

  // drawer
  let showDetailDrawer = false;
  let selectedProject: any = null;

  let showActivityDetailDrawer = false;
  let selectedActivity: any = null;

  // form
  let form = {
    name: '',
    description: '',
    status: '',
    start_date: '',
    finish_date: '',
    mitra_id: '',
    kategori: '',
    lokasi: '',
    no_po: '',
    no_so: '',
    is_cert_projects: false,
  };

  let projectStatuses: string[] = [];
  let projectKategoris: string[] = [];

  // Permissions derived from store (reactive)
  let canCreate = false;
  let canUpdate = false;
  let canDelete = false;
  let canViewActivity = false;

  // Use Svelte auto-subscription to userPermissions store
  // $userPermissions will update reactively
  $: {
    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
    const perms = $userPermissions ?? [];
    canCreate = perms.includes('project-create');
    canUpdate = perms.includes('project-update');
    canDelete = perms.includes('project-delete');
    canViewActivity = perms.includes('activity-view');
  }

  async function fetchProjects() {
    loading = true;
    error = '';
    try {
      const response = await axiosClient.get('/projects', {
        params: {
          search: search || undefined,
          status: statusFilter || undefined,
          kategori: kategoriFilter || undefined,
          is_cert_projects: certProjectFilter ? 1 : undefined,
          date_from: dateFromFilter || undefined,
          date_to: dateToFilter || undefined,
          page: currentPage,
          per_page: perPage,
          sort_by: sortBy,
          sort_dir: sortDir,
        }
      });
      
      const payload = response.data || {};
      projects = payload.data || [];
      const meta = payload.meta || payload.pagination || {};
      currentPage = meta.current_page || payload.current_page || 1;
      lastPage = meta.last_page || payload.last_page || 1;
      totalProjects = meta.total || payload.total || projects.length;

      if (payload.form_dependencies) {
        customers = Array.isArray(payload.form_dependencies.customers) ? payload.form_dependencies.customers : [];
        projectStatuses = Array.isArray(payload.form_dependencies.project_status_list) ? payload.form_dependencies.project_status_list : [];
        projectKategoris = Array.isArray(payload.form_dependencies.project_kategori_list) ? payload.form_dependencies.project_kategori_list : [];
      }
    } catch (err: any) {
      error = err.response?.data?.message || 'Gagal memuat project.';
      console.error('Error fetching projects:', err);
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    fetchProjects();
    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
  });

  function handleFilterOrSearch() {
    currentPage = 1;
    fetchProjects();
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
    statusFilter = '';
    kategoriFilter = '';
    dateFromFilter = '';
    dateToFilter = '';
    sortBy = 'created';
    sortDir = 'desc';
    showDateFilter = false;
    currentPage = 1;
    fetchProjects();
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
      fetchProjects();
    }
  }

  function openCreateModal() {
    if (!canCreate) {
      // safety: do not open if user lacks permission
      console.warn('User tried to open create modal but lacks project-create permission');
      return;
    }
    form = {
      name: '',
      description: '',
      status: '',
      start_date: '',
      finish_date: '',
      mitra_id: '',
      kategori: '',
      lokasi: '',
      no_po: '',
      no_so: '',
      is_cert_projects: false,
    };
    showCreateModal = true;
  }

  function openEditModal(project: any) {
    if (!canUpdate) {
      console.warn('User tried to open edit modal but lacks project-update permission');
      return;
    }
    editingProject = { ...project };
    form = {
      ...editingProject,
      mitra_id: editingProject.mitra_id || '',
      is_cert_projects: editingProject.is_cert_projects || false,
    };
    showEditModal = true;
  }

  function openDetailDrawer(project: any) {
    selectedProject = { ...project };
    showDetailDrawer = true;
  }

  function openActivityDetail(act: any, project: any) {
    selectedActivity = { ...act, project };
    showActivityDetailDrawer = true;
  }

  async function handleSubmitCreate() {
    if (!canCreate) {
      console.warn('Create attempt blocked: no permission');
      return;
    }
    try {
      await axiosClient.post('/projects', form);
      alert('Project berhasil ditambahkan!');
      goto(`/projects`);
      showCreateModal = false;
      fetchProjects();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal menambahkan project.';
      alert('Error:\n' + messages);
      console.error('Create project failed:', err.response || err);
    }
  }

  async function handleSubmitUpdate() {
    if (!editingProject?.id) return;
    if (!canUpdate) {
      console.warn('Update attempt blocked: no permission');
      return;
    }
    try {
      await axiosClient.put(`/projects/${editingProject.id}`, form);
      alert('Project berhasil diperbarui!');
      goto(`/projects`);
      showEditModal = false;
      fetchProjects();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal memperbarui project.';
      alert('Error:\n' + messages);
      console.error('Update project failed:', err.response || err);
    }
  }

  async function handleDelete(projectId: number) {
    if (!canDelete) {
      console.warn('Delete attempt blocked: no permission');
      return;
    }
    if (confirm('Apakah Anda yakin ingin menghapus project ini?')) {
      try {
        await axiosClient.delete(`/projects/${projectId}`);
        alert('Project berhasil dihapus!');
        goto(`/projects`);
        fetchProjects();
      } catch (err: any) {
        alert('Gagal menghapus project: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
        console.error('Delete project failed:', err.response || err);
      }
    }
  }

  // === Konsisten dengan dashboard: badge status dark ===
  function getStatusBadgeClasses(status: string) {
    switch (status) {
      case 'Complete': return 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
      case 'Ongoing': return 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200';
      case 'Prospect': return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200';
      case 'Cancel': return 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
      default: return 'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200';
    }
  }

  // --- Kegiatan ---
  let openActivities: Record<number, boolean> = {};

  function toggleActivities(id: number) {
    const currentState = !!openActivities[id];
    openActivities = {}; // Tutup semua (eksklusif)
    openActivities[id] = !currentState;
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
  $: lockBodyScroll(showDetailDrawer || showActivityDetailDrawer || showCreateModal || showEditModal);
</script>

<svelte:head>
  <title>Daftar Project - Indogreen</title>
</svelte:head>

<!-- Filter bar -->
<div class="flex flex-col sm:flex-row items-center justify-between mb-4 space-y-4 sm:space-y-0 sm:space-x-4">
  <div class="flex w-full sm:w-auto space-x-2">
    <select
      bind:value={statusFilter}
      on:change={handleFilterOrSearch}
      class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold
             bg-white text-gray-900 border border-gray-300
             dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"
    >
      <option value="">Status: Semua</option>
      {#each projectStatuses as status (status)}
        <option value={status}>{status}</option>
      {/each}
    </select>

    <select
      bind:value={kategoriFilter}
      on:change={handleFilterOrSearch}
      class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold
             bg-white text-gray-900 border border-gray-300
             dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"
    >
      <option value="">Kategori: Semua</option>
      {#each projectKategoris as kategori (kategori)}
        <option value={kategori}>{kategori}</option>
      {/each}
    </select>

    <div class="flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-semibold
                bg-white text-gray-900 border border-gray-300
                dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700">
      <input
        type="checkbox"
        id="cert_project_filter"
        bind:checked={certProjectFilter}
        on:change={handleFilterOrSearch}
        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded dark:border-gray-700"
      />
      <label for="cert_project_filter" class="text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
        Certificate
      </label>
    </div>
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
        placeholder="Cari project..."
        bind:value={search}
        on:input={handleSearchDebounced}
        class="block w-full pl-10 pr-3 py-2 border rounded-md leading-5
               bg-white text-gray-900 placeholder-gray-500 border-gray-300
               focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm
               dark:bg-neutral-900 dark:text-gray-100 dark:placeholder-gray-400 dark:border-gray-700"
      />
    </div>
  </div>

  {#if canCreate}
    <button
      on:click={openCreateModal}
      class="px-4 py-2 w-full sm:w-auto border border-transparent text-sm font-medium rounded-md shadow-sm
             text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
             dark:focus:ring-offset-gray-800"
    >
      Tambah Project
    </button>
  {/if}
</div>

<!-- Rest of UI remains the same but wrap Edit/Delete buttons with permission checks -->
<!-- Switch Table/Simple -->
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

  <!-- Date filter -->
  <div class="relative">
    <button
      on:click={toggleDateFilter}
      class="date-filter-button px-3 py-2 rounded-md text-sm font-semibold border flex items-center space-x-1 transition-colors
             bg-white text-gray-900 border-gray-300 hover:bg-gray-50
             dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700 dark:hover:bg-neutral-800"
      class:bg-indigo-50={dateFromFilter || dateToFilter}
      class:border-indigo-300={dateFromFilter || dateToFilter}
      class:text-indigo-700={dateFromFilter || dateToFilter}
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
      </svg>
      <span>Filter Tanggal</span>
      {#if dateFromFilter || dateToFilter}
        <div class="w-2 h-2 bg-indigo-500 rounded-full"></div>
      {/if}
      <svg class="w-4 h-4 transition-transform" class:rotate-180={showDateFilter} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
      </svg>
    </button>

    {#if showDateFilter}
      <div
        class="date-filter-dropdown absolute right-0 mt-2 w-80 bg-white border border-gray-300 rounded-md shadow-lg z-10 p-4
               dark:bg-neutral-900 dark:border-gray-700"
      >
        <div class="space-y-3">
          <div>
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Urutkan Berdasarkan Create
            </span>
            <select
              bind:value={sortDir}
              on:change={() => { sortBy = 'created'; handleFilterOrSearch(); }}
              class="w-full mb-2 px-3 py-2 rounded-md text-sm font-semibold
                    bg-white text-gray-900 border border-gray-300
                    dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"
              title="Urutkan berdasarkan waktu dibuat"
            >
              <option value="desc">Terbaru</option>
              <option value="asc">Terlama</option>
            </select>
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Urutkan Tanggal Dilaksanakan
            </span>
            <div
              class="inline-flex w-full rounded-md overflow-hidden border border-gray-300 dark:border-gray-700"
              role="tablist"
              aria-label="Urutan tanggal dilaksanakan"
            >
              <button
                type="button"
                on:click={() => { sortBy = 'start_date'; sortDir = 'desc'; currentPage = 1; fetchProjects(); }}
                class="w-full px-3 py-1.5 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                class:bg-indigo-600={sortBy==='start_date' && sortDir==='desc'}
                class:text-white={sortBy==='start_date' && sortDir==='desc'}
                class:bg-white={!(sortBy==='start_date' && sortDir==='desc')}
                class:text-gray-900={!(sortBy==='start_date' && sortDir==='desc')}
                class:dark:bg-neutral-900={!(sortBy==='start_date' && sortDir==='desc')}
                class:dark:text-gray-100={!(sortBy==='start_date' && sortDir==='desc')}
                aria-selected={sortBy==='start_date' && sortDir==='desc'}
                role="tab"
              >
                Terbaru dulu
              </button>
              <button
                type="button"
                on:click={() => { sortBy = 'start_date'; sortDir = 'asc'; currentPage = 1; fetchProjects(); }}
                class="w-full px-3 py-1.5 text-sm font-semibold transition-colors border-l border-gray-300 dark:border-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                class:bg-indigo-600={sortBy==='start_date' && sortDir==='asc'}
                class:text-white={sortBy==='start_date' && sortDir==='asc'}
                class:bg-white={!(sortBy==='start_date' && sortDir==='asc')}
                class:text-gray-900={!(sortBy==='start_date' && sortDir==='asc')}
                class:dark:bg-neutral-900={!(sortBy==='start_date' && sortDir==='asc')}
                class:dark:text-gray-100={!(sortBy==='start_date' && sortDir==='asc')}
                aria-selected={sortBy==='start_date' && sortDir==='asc'}
                role="tab"
              >
                Terlama dulu
              </button>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
              Gunakan menu <b>Clear All</b> di paling bawah untuk kembali ke urutan <b>Create Terbaru</b>.
            </p>
          </div>
          {#if dateFromFilter || dateToFilter}
            <div class="text-xs text-gray-500 bg-gray-50 dark:bg-neutral-800 dark:text-gray-300 p-2 rounded">
              {#if dateFromFilter && dateToFilter}
                Filter: {new Date(dateFromFilter).toLocaleDateString('id-ID')} - {new Date(dateToFilter).toLocaleDateString('id-ID')}
              {:else if dateFromFilter}
                Dari: {new Date(dateFromFilter).toLocaleDateString('id-ID')}
              {:else if dateToFilter}
                Sampai: {new Date(dateToFilter).toLocaleDateString('id-ID')}
              {/if}
            </div>
          {/if}
          <div>
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal</span>
            <input
              type="date"
              bind:value={dateFromFilter}
              on:change={handleFilterOrSearch}
              class="w-full px-3 py-2 rounded-md text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500
                     dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"
            />
          </div>
          <div>
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal</span>
            <input
              type="date"
              bind:value={dateToFilter}
              on:change={handleFilterOrSearch}
              class="w-full px-3 py-2 rounded-md text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500
                     dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"
            />
          </div>
          <div class="flex space-x-2 pt-2">
            <button
              on:click={clearFilters}
              class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200
                     dark:text-gray-200 dark:bg-neutral-800 dark:border-gray-700 dark:hover:bg-neutral-700"
            >
              Clear All
            </button>
            <button
              on:click={() => { showDateFilter = false; }}
              class="flex-1 px-3 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    {/if}
  </div>
</div>
<!-- Example in list view: -->
{#if loading}
  <p class="text-gray-900 dark:text-white">Memuat project...</p>
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if projects.length === 0}
  <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
      <li class="px-4 py-4 sm:px-6">
        <p class="text-sm text-gray-500 dark:text-gray-300">Belum ada project.</p>
      </li>
    </ul>
  </div>
{:else}
  {#if activeView === 'list'}
    <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
      <ul class="divide-y divide-gray-200 dark:divide-gray-700">
        {#each projects as project (project.id)}
          <li>
            <a href={`/projects/${project.id}`} class="block hover:bg-gray-50 dark:hover:bg-neutral-950 px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">
                  {project.name}
                </p>
                <div class="ml-2 flex-shrink-0 flex space-x-2">
                  <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {getStatusBadgeClasses(project.status)}">
                    {project.status}
                  </span>
                  {#if project.is_cert_projects}
                    <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                      Certificate
                    </span>
                  {/if}
                </div>
              </div>
              <div class="mt-2 sm:flex sm:justify-between">
                <div class="sm:flex">
                  <p class="flex items-center text-sm text-gray-500 dark:text-gray-300">
                    Customer: {project.mitra?.nama || '-'} | Deskripsi: {project.description.substring(0, 50)}{project.description.length > 50 ? '...' : ''}
                  </p>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-300 sm:mt-0">
                  <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                  </svg>
                  <p>
                    Mulai: {new Date(project.start_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}
                  </p>
                </div>
              </div>
            </a>

            <div class="flex justify-end px-4 py-2 sm:px-6 space-x-2">
            {#if canViewActivity}
              <button
                on:click|stopPropagation={() => toggleActivities(project.id)}
                class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  ><rect x="3" y="5" width="6" height="6" rx="1" /><path d="m3 17 2 2 4-4" /><path
                    d="M13 6h8"
                  /><path d="M13 12h8" /><path d="M13 18h8" /></svg
                >
                Kegiatan
              </button>
            {/if}

              <button on:click|stopPropagation={() => openDetailDrawer(project)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-yellow-600 hover:bg-yellow-700">
                Detail
              </button>

              {#if canUpdate}
                <button on:click|stopPropagation={() => openEditModal(project)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                  Edit
                </button>
              {/if}

              {#if canDelete}
                <button on:click|stopPropagation={() => handleDelete(project.id)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                  Hapus
                </button>
              {/if}
            </div>

            {#if canViewActivity && openActivities[project.id]}
              <div
                class="mt-0 border-t border-gray-100 bg-gray-50/50 p-5 dark:border-neutral-800 dark:bg-neutral-900/30"
              >
                <h4 class="mb-5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                  Riwayat Kegiatan Proyek
                </h4>

                <div class="relative pl-8 pr-4">
                  <!-- Garis Timeline -->
                  <div
                    class="absolute top-0 bottom-0 left-[15px] w-[2px] bg-gray-200 dark:bg-neutral-800"
                  ></div>

                  <div class="space-y-7">
                    {#if !project.activities || project.activities.length === 0}
                      <p class="py-2 text-xs text-gray-500 italic">Tidak ada kegiatan tercatat</p>
                    {:else}
                      {#each project.activities as act (act.id)}
                        <div class="relative">
                          <!-- Dot Timeline -->
                          <div
                            class="absolute -left-[21px] top-1.5 h-4 w-4 rounded-full border-2 border-white bg-emerald-500 ring-2 ring-emerald-500/20 dark:border-black"
                          ></div>

                          <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                            <div class="min-w-[130px] flex-shrink-0">
                              <span class="text-xs font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                                {act.activity_date
                                  ? new Date(act.activity_date).toLocaleDateString('id-ID', {
                                      day: '2-digit',
                                      month: 'long',
                                      year: 'numeric'
                                    })
                                  : '-'}
                              </span>
                            </div>
                            <div class="flex-1">
                              <div class="flex items-center gap-2">
                                <!-- svelte-ignore a11y_click_events_have_key_events -->
                                <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
                                <h5
                                  on:click={() => openActivityDetail(act, project)}
                                  class="cursor-pointer text-sm font-bold leading-tight text-gray-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                >
                                  {act.name}
                                </h5>
                                <span
                                  class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                >
                                  {act.kategori || 'Umum'}
                                </span>
                              </div>
                              {#if act.mitra}
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                  Jenis: 
                                  <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {act.jenis || '-'}
                                  </span>
                                  {#if (act.jenis === 'Customer' || act.jenis === 'Vendor') && act.mitra}
                                    | Mitra: 
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                      {act.mitra.nama || '-'}
                                    </span>
                                  {/if}
                                </p>
                              {/if}
                            </div>
                          </div>
                        </div>
                      {/each}
                    {/if}
                  </div>
                </div>
              </div>
            {/if}
          </li>
        {/each}
      </ul>

      {#if projects.length > 0}
        <Pagination
          currentPage={currentPage}
          lastPage={lastPage}
          onPageChange={goToPage}
          totalItems={totalProjects}
          itemsPerPage={perPage}
          perPageOptions={perPageOptions}
          onPerPageChange={(n) => { perPage = n; currentPage = 1; fetchProjects(); }}
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
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Nama Project</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Lokasi</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Tahun</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Kategori</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Dilaksanakan</th>
              <th scope="col" class="relative px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-black">
            {#each projects as project (project.id)}
              <tr>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                  <a href={`/projects/${project.id}`} class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300" title="Detail">
                    {project.name}
                  </a><br>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{project.mitra.nama}</span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {project.lokasi.substring(0, 40)}{project.lokasi.length > 40 ? '...' : ''}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {new Date(project.start_date).toLocaleDateString('id-ID', { year: 'numeric' })}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {project.kategori || '-'}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  <div class="flex items-center">
                    <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {getStatusBadgeClasses(project.status)}">
                      {project.status}
                    </span>
                    {#if project.is_cert_projects}
                      <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 ml-2">
                        Certificate
                      </span>
                    {/if}
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {new Date(project.start_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}<br>
                  {#if project.finish_date}
                    {new Date(project.finish_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                  {:else}
                    -
                  {/if}
                </td>
                <td class="relative whitespace-nowrap px-3 py-4 text-left text-sm font-medium">
                  <div class="flex items-left space-x-2">
                    {#if canViewActivity}
                      <button
                        on:click|stopPropagation={() => toggleActivities(project.id)}
                        class="transition-colors {openActivities[project.id]
                          ? 'text-emerald-600 dark:text-emerald-400'
                          : 'text-gray-400 hover:text-emerald-500'}"
                        aria-label="Lihat Kegiatan"
                        title="Daftar Kegiatan"
                      >
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          width="20"
                          height="20"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="2"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          ><rect x="3" y="5" width="6" height="6" rx="1" /><path d="m3 17 2 2 4-4" /><path
                            d="M13 6h8"
                          /><path d="M13 12h8" /><path d="M13 18h8" /></svg
                        >
                      </button>
                    {/if}
                    <button on:click={() => openDetailDrawer(project)} class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" title="Detail">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      <span class="sr-only">Detail, {project.name}</span>
                    </button>

                    {#if canUpdate}
                      <button on:click|stopPropagation={() => openEditModal(project)} title="Edit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span class="sr-only">Edit, {project.name}</span>
                      </button>
                    {/if}

                    {#if canDelete}
                      <button on:click|stopPropagation={() => handleDelete(project.id)} title="Delete" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        <span class="sr-only">Hapus, {project.name}</span>
                      </button>
                    {/if}
                  </div>
                </td>
              </tr>

              {#if canViewActivity && openActivities[project.id]}
                <tr class="bg-gray-50/70 transition-all dark:bg-neutral-900/50">
                  <td colspan="7" class="px-3 py-0">
                    <div class="relative py-5 pl-14 pr-4">
                      <!-- Garis Vertikal Timeline -->
                      <div class="absolute top-0 bottom-0 left-9 w-[2px] bg-gray-200 dark:bg-neutral-800"></div>

                      <div class="space-y-6">
                        {#if !project.activities || project.activities.length === 0}
                          <div class="py-2 text-sm text-gray-500 italic">
                            Belum ada aktivitas tercatat untuk proyek ini.
                          </div>
                        {:else}
                          {#each project.activities as act (act.id)}
                            <div class="relative">
                              <!-- Dot Timeline -->
                              <div
                                class="absolute -left-[23px] top-1.5 h-4 w-4 rounded-full border-2 border-white bg-emerald-500 ring-2 ring-emerald-500/20 dark:border-black"
                              ></div>

                              <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                                <div class="min-w-[130px] flex-shrink-0">
                                  <span class="text-xs font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                                    {act.activity_date
                                      ? new Date(act.activity_date).toLocaleDateString('id-ID', {
                                          day: '2-digit',
                                          month: 'long',
                                          year: 'numeric'
                                        })
                                      : '-'}
                                  </span>
                                </div>
                                <div class="flex-1">
                                  <div class="flex items-center gap-2">
                                    <!-- svelte-ignore a11y_click_events_have_key_events -->
                                    <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
                                    <h5
                                      on:click={() => openActivityDetail(act, project)}
                                      class="cursor-pointer text-sm font-bold text-gray-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                    >
                                      {act.name}
                                    </h5>
                                    <span
                                      class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    >
                                      {act.kategori || 'Umum'}
                                    </span>
                                  </div>
                                  {#if act.mitra}
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                      Jenis: 
                                      <span class="font-medium text-gray-700 dark:text-gray-300">
                                        {act.jenis || '-'}
                                      </span>
                                      {#if (act.jenis === 'Customer' || act.jenis === 'Vendor') && act.mitra}
                                        | Mitra: 
                                        <span class="font-medium text-gray-700 dark:text-gray-300">
                                          {act.mitra.nama || '-'}
                                        </span>
                                      {/if}
                                    </p>
                                  {/if}
                                </div>
                              </div>
                            </div>
                          {/each}
                        {/if}
                      </div>
                    </div>
                  </td>
                </tr>
              {/if}
            {/each}
          </tbody>
        </table>
      </div>

      {#if projects.length > 0}
        <Pagination
          currentPage={currentPage}
          lastPage={lastPage}
          onPageChange={goToPage}
          totalItems={totalProjects}
          itemsPerPage={perPage}
          perPageOptions={perPageOptions}
          onPerPageChange={(n) => { perPage = n; currentPage = 1; fetchProjects(); }}
        />
      {/if}
    </div>
  {/if}
{/if}

<!-- Modals -->
<ProjectFormModal
  bind:show={showCreateModal}
  title="Form Project Baru"
  submitLabel="Tambah Project"
  idPrefix="create"
  {form}
  {customers}
  {projectStatuses}
  {projectKategoris}
  onSubmit={handleSubmitCreate}
/>

{#if editingProject}
  <ProjectFormModal
    bind:show={showEditModal}
    title="Edit Project"
    submitLabel="Update Project"
    idPrefix="edit"
    {form}
    {customers}
    {projectStatuses}
    {projectKategoris}
    onSubmit={handleSubmitUpdate}
  />
{/if}

<!-- Drawer detail -->
<Drawer bind:show={showDetailDrawer} title="Detail Project" on:close={() => (showDetailDrawer = false)}>
  <ProjectDetail project={selectedProject} />
</Drawer>

<!-- Drawer detail kegiatan -->
<Drawer bind:show={showActivityDetailDrawer} title="Detail Kegiatan" on:close={() => (showActivityDetailDrawer = false)}>
  <ActivityDetail activity={selectedActivity} />
</Drawer>
