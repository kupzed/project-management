<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import axiosClient from '$lib/axiosClient';
  import Drawer from '$lib/components/Drawer.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import ActivityDetail from '$lib/components/detail/ActivityDetail.svelte';
  import ActivityFormModal from '$lib/components/form/ActivityFormModal.svelte';
  import { userPermissions } from '$lib/stores/permissions';

  // List of activities and form dependencies
  let activities: any[] = [];
  let projects: any[] = [];
  let vendors: any[] = [];
  let customers: any[] = [];
  let loading = true;
  let error = '';

  // Filters and search state
  let search: string = '';
  let jenisFilter: string = '';
  let kategoriFilter: string = '';
  let dateFromFilter: string = '';
  let dateToFilter: string = '';
  let showDateFilter: boolean = false;
  let currentPage: number = 1;
  let lastPage: number = 1;
  let totalActivities: number = 0;
  let perPage: number = 50;
  const perPageOptions = [10, 25, 50, 100];

  // view toggle (table/list)
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

  // Modal state
  let showCreateModal: boolean = false;
  let showEditModal: boolean = false;
  let editingActivity: any = null;
  let showDetailDrawer: boolean = false;
  let selectedActivity: any = null;

  // === FORM STATE (multi-file) ===
  // form includes existing_attachments with optional description and original_name
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

  // Permissions derived from store (reactive)
  let canCreateActivity = false;
  let canUpdateActivity = false;
  let canDeleteActivity = false;

  $: {
    const perms = $userPermissions ?? [];
    canCreateActivity = perms.includes('activity-create');
    canUpdateActivity = perms.includes('activity-update');
    canDeleteActivity = perms.includes('activity-delete');
  }

  // fetch list of activities with filters
  let sortBy: 'created' | 'activity_date' = 'activity_date';
  let sortDir: 'desc' | 'asc' = 'asc';

  async function fetchActivities() {
    loading = true; error = '';
    try {
      const filters = {
        search,
        jenis: jenisFilter,
        kategori: kategoriFilter,
        date_from: dateFromFilter,
        date_to: dateToFilter,
        page: currentPage,
        per_page: perPage,
        sort_by: sortBy,
        sort_dir: sortDir
      };

      // Strip empty/null/undefined params dynamically
      const cleanParams: Record<string, any> = {};
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          cleanParams[key] = value;
        }
      });

      const response = await axiosClient.get('/activities', { params: cleanParams });
      activities = response.data.data;
      currentPage = response.data.meta?.current_page || 1;
      lastPage = response.data.meta?.last_page || 1;
      totalActivities = response.data.meta?.total || 0;

      // Extract form dependencies from the same response (only load once or update lightly)
      if (response.data.form_dependencies) {
        const deps = response.data.form_dependencies;
        projects = deps.projects || [];
        vendors = deps.vendors || [];
        customers = deps.customers || [];
        activityKategoriList = Array.isArray(deps.kategori_list) ? deps.kategori_list : [];
        activityJenisList = Array.isArray(deps.jenis_list) ? deps.jenis_list : [];

        // assign mitra to projects based on vendors/customers
        if (Array.isArray(projects)) {
          const mitraMap = new Map<any, any>();
          if (Array.isArray(vendors)) vendors.forEach((v: any) => mitraMap.set(v.id, v));
          if (Array.isArray(customers)) customers.forEach((c: any) => mitraMap.set(c.id, c));
          projects = projects.map((p: any) => ({
            ...p,
            mitra: p.mitra || (p.mitra_id ? mitraMap.get(p.mitra_id) : (p.customer_id ? mitraMap.get(p.customer_id) : undefined))
          }));
        }
      }
    } catch (err: any) {
      error = err.response?.data?.message || 'Gagal memuat aktivitas.';
      console.error('Error fetching activities:', err);
    } finally {
      loading = false;
    }
  }



  // lifecycle: fetch activities and dependencies on mount
  onMount(() => {
    fetchActivities();
    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
  });

  // handle filter or search change
  function handleFilterOrSearch() {
    currentPage = 1;
    fetchActivities();
  }
  let searchTimer: ReturnType<typeof setTimeout>;

  function handleSearchDebounced() {
    clearTimeout(searchTimer);
    
    searchTimer = setTimeout(() => {
      handleFilterOrSearch();
    }, 800);
  }
  // clear filters
  function clearFilters() {
    search = '';
    jenisFilter = '';
    kategoriFilter = '';
    dateFromFilter = '';
    dateToFilter = '';
    sortBy = 'activity_date';
    sortDir = 'asc';
    showDateFilter = false;
    currentPage = 1;
    fetchActivities();
  }
  // toggle date filter dropdown
  function toggleDateFilter() {
    showDateFilter = !showDateFilter;
  }
  // close date filter dropdown when clicking outside
  function handleClickOutside(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest('.date-filter-dropdown') && !target.closest('.date-filter-button')) {
      showDateFilter = false;
    }
  }
  // pagination helper
  function goToPage(page: number) {
    if (page > 0 && page <= lastPage) {
      currentPage = page;
      fetchActivities();
    }
  }

  // open create modal: reset form
  function openCreateModal() {
    if (!canCreateActivity) {
      console.warn('User lacks activity-create permission');
      return;
    }
    form = {
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
    showCreateModal = true;
  }

  // open edit modal: populate form with existing activity
  function openEditModal(activity: any) {
    if (!canUpdateActivity) {
      console.warn('User lacks activity-update permission');
      return;
    }
    editingActivity = { ...activity };
    // convert date to YYYY-MM-DD for input
    editingActivity.activity_date = activity.activity_date ? new Date(activity.activity_date).toISOString().split('T')[0] : '';
    form = {
      name: editingActivity.name ?? '',
      short_desc: editingActivity.short_desc ?? '',
      description: editingActivity.description ?? '',
      value: editingActivity.value ?? 0,
      project_id: editingActivity.project_id ?? '',
      kategori: editingActivity.kategori ?? '',
      activity_date: editingActivity.activity_date ?? '',
      jenis: editingActivity.jenis ?? '',
      mitra_id: editingActivity.mitra_id ?? '',
      from: editingActivity.from ?? '',
      to: editingActivity.to ?? '',
      attachments: [],
      attachment_names: [],
      attachment_descriptions: [],
      // map existing attachments to include id, name, description, original_name, url and size
      existing_attachments: Array.isArray(editingActivity.attachments)
        ? editingActivity.attachments.map((a: any) => ({
            id: a.id,
            name: a.name ?? a.file_name ?? 'Lampiran',
            description: a.description ?? '',
            original_name: a.original_name ?? a.file_name ?? a.name ?? '',
            url: a.url ?? a.path ?? a.file_path,
            size: a.size
          }))
        : [],
      removed_existing_ids: []
    };

    // ensure mitra_id is set correctly for Customer jenis
    if (form.jenis === 'Customer' && form.project_id) {
      const selectedProject = projects.find((p) => p.id == form.project_id);
      if (selectedProject?.mitra_id) form.mitra_id = selectedProject.mitra_id;
    }

    showEditModal = true;
  }

  // open detail drawer
  function openDetailDrawer(activity: any) {
    selectedActivity = { ...activity };
    showDetailDrawer = true;
  }

  // helper to append scalar values to FormData
  function appendScalar(fd: FormData, key: string, val: any) {
    if (val === null || val === undefined || val === '') return;
    fd.append(key, String(val));
  }

  // build FormData for creating/updating activity
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

    // determine mitra_id based on jenis
    if (form.jenis === 'Internal') {
      fd.set('mitra_id', '1');
    } else if (form.jenis === 'Customer') {
      const selectedProject = projects.find((p) => p.id == form.project_id);
      if (selectedProject?.mitra_id) fd.set('mitra_id', String(selectedProject.mitra_id));
    } else if (form.jenis === 'Vendor' && form.mitra_id) {
      fd.set('mitra_id', String(form.mitra_id));
    }

    // new attachments
    (form.attachments || []).forEach((file, i) => {
      fd.append(`attachments[${i}]`, file);
    });
    (form.attachment_names || []).forEach((n, i) => {
      if (n != null) fd.append(`attachment_names[${i}]`, n);
    });
    (form.attachment_descriptions || []).forEach((d, i) => {
      if (d != null) fd.append(`attachment_descriptions[${i}]`, d);
    });

    // existing attachments edits: send ids, names, descriptions
    (form.existing_attachments || []).forEach((att, i) => {
      fd.append(`existing_attachment_ids[${i}]`, String(att.id));
      fd.append(`existing_attachment_names[${i}]`, att.name);
      fd.append(`existing_attachment_descriptions[${i}]`, att.description ?? '');
    });

    // removed existing attachments
    (form.removed_existing_ids || []).forEach((id) => {
      fd.append('removed_existing_ids[]', String(id));
    });

    return fd;
  }

  // submit handlers
  async function handleSubmitCreate() {
    if (!canCreateActivity) {
      console.warn('Create activity blocked by permission');
      return;
    }
    try {
      const formData = buildFormDataForActivity();
      await axiosClient.post('/activities', formData, { headers: { 'Content-Type':'multipart/form-data' } });
      alert('Aktivitas berhasil ditambahkan!');
      goto(`/activities`);
      showCreateModal = false;
      fetchActivities();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal menambahkan aktivitas.';
      alert('Error:\n' + messages);
      console.error('Create activity failed:', err.response || err);
    }
  }

  async function handleSubmitUpdate() {
    if (!canUpdateActivity) {
      console.warn('Update activity blocked by permission');
      return;
    }
    if (!editingActivity?.id) return;
    try {
      const formData = buildFormDataForActivity();
      formData.append('_method', 'PUT');
      await axiosClient.post(`/activities/${editingActivity.id}`, formData, { headers: { 'Content-Type':'multipart/form-data' } });
      alert('Aktivitas berhasil diperbarui!');
      goto(`/activities`);
      showEditModal = false;
      fetchActivities();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal memperbarui aktivitas.';
      alert('Error:\n' + messages);
      console.error('Update activity failed:', err.response || err);
    }
  }

  async function handleDelete(activityId: number) {
    if (!canDeleteActivity) {
      console.warn('Delete activity blocked by permission');
      return;
    }
    if (!confirm('Apakah Anda yakin ingin menghapus aktivitas ini?')) return;
    try {
      await axiosClient.delete(`/activities/${activityId}`);
      alert('Aktivitas berhasil dihapus!');
      goto(`/activities`);
      fetchActivities();
    } catch (err: any) {
      alert('Gagal menghapus aktivitas: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
      console.error('Delete activity failed:', err.response || err);
    }
  }

  // reactive: adjust mitra_id based on jenis
  let previousJenis = '';
  $: if ((showCreateModal || showEditModal) && form.jenis && form.jenis !== previousJenis) {
    previousJenis = form.jenis;
    if (form.jenis === 'Customer') {
      const selectedProject = projects.find((p) => p.id == form.project_id);
      form.mitra_id = selectedProject?.mitra_id || null;
    } else if (form.jenis === 'Internal') {
      form.mitra_id = '1';
    } else if (form.jenis === 'Vendor') {
      if (!Array.isArray(vendors) || !vendors.some((v) => v.id == form.mitra_id)) form.mitra_id = '';
    } else {
      form.mitra_id = '';
    }
  }
  $: if (form.jenis === 'Customer' && form.project_id) {
    const selectedProject = projects.find((p) => p.id == form.project_id);
    if (selectedProject?.mitra_id && form.mitra_id !== selectedProject.mitra_id) form.mitra_id = selectedProject.mitra_id;
  }
  $: if (!showCreateModal && !showEditModal) {
    form.mitra_id = '';
    form.jenis = '';
    previousJenis = '';
  }
</script>

<svelte:head>
  <title>Daftar Activity - Indogreen</title>
</svelte:head>

<!-- Toolbar: filters and search -->
<div class="flex flex-col sm:flex-row items-center justify-between mb-4 space-y-4 sm:space-y-0 sm:space-x-4">
  <div class="flex w-full sm:w-auto space-x-2">
    <select bind:value={jenisFilter} on:change={handleFilterOrSearch}
      class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300 dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700">
      <option value="">Jenis: Semua</option>
      {#each activityJenisList as jenis}<option value={jenis}>{jenis}</option>{/each}
    </select>
    <select bind:value={kategoriFilter} on:change={handleFilterOrSearch}
      class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300 dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700">
      <option value="">Kategori: Semua</option>
      {#each activityKategoriList as kategori}<option value={kategori}>{kategori}</option>{/each}
    </select>
  </div>

  <div class="w-full sm:w-auto flex-grow">
    <div class="relative w-full sm:w-auto">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"/></svg>
      </div>
      <input
        type="text" placeholder="Cari aktivitas..." bind:value={search} on:input={handleSearchDebounced}
        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white text-gray-900 placeholder-gray-500
               focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm
               dark:bg-neutral-900 dark:text-gray-100 dark:placeholder-gray-400 dark:border-gray-700"
      />
    </div>
  </div>

  {#if canCreateActivity}
    <button
      on:click={openCreateModal}
      class="px-4 py-2 w-full sm:w-auto border border-transparent text-sm font-medium rounded-md shadow-sm text-white
             bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
             dark:focus:ring-offset-gray-800">
      Tambah Aktivitas
    </button>
  {/if}
</div>

<!-- Date filter button and dropdown -->
<div class="flex items-center justify-between mb-4">
  <!-- View toggle -->
  <div
    class="bg-white dark:bg-neutral-900 rounded-md inline-flex gap-1"
    role="tablist"
    aria-label="Switch view"
    tabindex="0"
    on:keydown={handleViewKeydown}
  >
    <!-- TABLE view -->
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
    <!-- LIST view -->
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
  <!-- Date filter toggle button and dropdown -->
  <div class="relative">
    <button
      on:click={toggleDateFilter}
      class="date-filter-button px-3 py-2 rounded-md text-sm font-semibold border flex items-center space-x-1 transition-colors
             hover:bg-gray-50
             class:bg-white class:border-gray-300 class:text-gray-900
             dark:text-gray-100 dark:bg-neutral-900 dark:border-gray-700 dark:hover:bg-neutral-800
             focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
      class:bg-indigo-50={dateFromFilter || dateToFilter}
      class:border-indigo-300={dateFromFilter || dateToFilter}
      class:text-indigo-700={dateFromFilter || dateToFilter}
      class:bg-white={!dateFromFilter && !dateToFilter}
      class:border-gray-300={!dateFromFilter && !dateToFilter}
      class:text-gray-900={!dateFromFilter && !dateToFilter}
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span>Filter Tanggal</span>
      {#if dateFromFilter || dateToFilter}<div class="w-2 h-2 bg-indigo-500 rounded-full"></div>{/if}
      <svg class="w-4 h-4 transition-transform" class:rotate-180={showDateFilter} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    {#if showDateFilter}
      <div class="date-filter-dropdown absolute right-0 mt-2 w-80 bg-white border border-gray-300 rounded-md shadow-lg z-10 p-4
                  dark:bg-neutral-900 dark:border-gray-700">
        <div class="space-y-3">
          <span class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
            Urutkan Berdasarkan Create
          </span>
          <div class="inline-flex w-full rounded-md overflow-hidden border border-gray-300 dark:border-gray-700" role="tablist" aria-label="Urutan tanggal aktivitas">
            <select
              bind:value={sortDir}
              on:change={() => { sortBy = 'created'; handleFilterOrSearch(); }}
              class="w-full px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900
                    dark:bg-neutral-900 dark:text-gray-100"
              title="Urutkan berdasarkan waktu dibuat"
            >
              <option value="desc">Terbaru</option>
              <option value="asc">Terlama</option>
            </select>
          </div>
          <span class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
            Urutkan Tanggal Aktivitas
          </span>
          <div class="inline-flex w-full rounded-md overflow-hidden border border-gray-300 dark:border-gray-700" role="tablist" aria-label="Urutan tanggal aktivitas">
            <button
              type="button"
              on:click={() => { sortBy='activity_date'; sortDir='desc'; currentPage=1; fetchActivities(); }}
              class="w-full px-3 py-1.5 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
              class:bg-indigo-600={sortBy==='activity_date' && sortDir==='desc'}
              class:text-white={sortBy==='activity_date' && sortDir==='desc'}
              class:bg-white={!(sortBy==='activity_date' && sortDir==='desc')}
              class:text-gray-900={!(sortBy==='activity_date' && sortDir==='desc')}
              class:dark:bg-neutral-900={!(sortBy==='activity_date' && sortDir==='desc')}
              class:dark:text-gray-100={!(sortBy==='activity_date' && sortDir==='desc')}
              aria-selected={sortBy==='activity_date' && sortDir==='desc'}
              role="tab"
            >
              Terbaru dulu
            </button>
            <button
              type="button"
              on:click={() => { sortBy='activity_date'; sortDir='asc'; currentPage=1; fetchActivities(); }}
              class="w-full px-3 py-1.5 text-sm font-semibold transition-colors border-l border-gray-300 dark:border-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
              class:bg-indigo-600={sortBy==='activity_date' && sortDir==='asc'}
              class:text-white={sortBy==='activity_date' && sortDir==='asc'}
              class:bg-white={!(sortBy==='activity_date' && sortDir==='asc')}
              class:text-gray-900={!(sortBy==='activity_date' && sortDir==='asc')}
              class:dark:bg-neutral-900={!(sortBy==='activity_date' && sortDir==='asc')}
              class:dark:text-gray-100={!(sortBy==='activity_date' && sortDir==='asc')}
              aria-selected={sortBy==='activity_date' && sortDir==='asc'}
              role="tab"
            >
              Terlama dulu
            </button>
          </div>
          {#if dateFromFilter || dateToFilter}
            <div class="text-xs text-gray-500 bg-gray-50 p-2 rounded dark:text-gray-300 dark:bg-neutral-800">
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
            <!-- svelte-ignore a11y_label_has_associated_control -->
            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">Dari Tanggal</label>
            <input type="date" bind:value={dateFromFilter} on:change={handleFilterOrSearch}
              class="w-full px-3 py-2 rounded-md text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"/>
          </div>
          <div>
            <!-- svelte-ignore a11y_label_has_associated_control -->
            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">Sampai Tanggal</label>
            <input type="date" bind:value={dateToFilter} on:change={handleFilterOrSearch}
              class="w-full px-3 py-2 rounded-md text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"/>
          </div>
          <div class="flex space-x-2 pt-2 border-t border-gray-200 dark:border-gray-700">
            <button on:click={clearFilters}
              class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200
                     dark:text-gray-200 dark:bg-neutral-800 dark:border-gray-700 dark:hover:bg-neutral-700">
              Clear All
            </button>
            <button on:click={() => { showDateFilter = false; }}
              class="flex-1 px-3 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700">
              Close
            </button>
          </div>
        </div>
      </div>
    {/if}
  </div>
</div>

<!-- List or table view -->
{#if loading}
  <p class="text-gray-900 dark:text-white">Memuat aktivitas...</p>
{:else if error}
  <p class="text-red-500">{error}</p>
{:else if activities.length === 0}
  <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
      <li class="px-4 py-4 sm:px-6">
        <p class="text-sm text-gray-500 dark:text-gray-300">Belum ada mitra.</p>
      </li>
    </ul>
  </div>
{:else}
  {#if activeView === 'list'}
    <!-- LIST view: each item as row with actions -->
    <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
      <ul class="divide-y divide-gray-200 dark:divide-gray-700">
        {#each activities as activity (activity.id)}
          <li>
            <a href={`/activities/${activity.id}`} class="block hover:bg-gray-50 dark:hover:bg-neutral-950 px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">{activity.name}</p>
                <div class="ml-2 flex-shrink-0 flex">
                  <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    {activity.kategori}
                  </span>
                </div>
              </div>
              <div class="mt-2 sm:flex sm:justify-between">
                <div class="sm:flex">
                  <p class="flex items-center text-sm text-gray-500 dark:text-gray-300">
                    Project: {activity.project?.name || '-'}
                    | Jenis: {activity.jenis}
                    {#if (activity.jenis === 'Vendor' || activity.jenis === 'Customer') && activity.mitra}
                      | {activity.jenis}: {activity.mitra.nama}
                    {/if}
                    | From: {activity.from || '-'} | Deskripsi: {activity.short_desc}
                  </p>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-300 sm:mt-0">
                  <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                  <p>Aktivitas: {new Date(activity.activity_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</p>
                </div>
              </div>
            </a>
            <div class="flex justify-end px-4 py-2 sm:px-6 space-x-2">
              <button on:click|stopPropagation={() => openDetailDrawer(activity)}
                class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-yellow-600 hover:bg-yellow-700">
                Detail
              </button>
              {#if canUpdateActivity}
                <button on:click|stopPropagation={() => openEditModal(activity)}
                  class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-blue-600 hover:bg-blue-700
                         focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                  Edit
                </button>
              {/if}
              {#if canDeleteActivity}
                <button on:click|stopPropagation={() => handleDelete(activity.id)}
                  class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-red-600 hover:bg-red-700
                         focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800">
                  Hapus
                </button>
              {/if}
            </div>
          </li>
        {/each}
      </ul>
      {#if activities.length > 0}
        <Pagination currentPage={currentPage} lastPage={lastPage} onPageChange={goToPage} totalItems={totalActivities} itemsPerPage={perPage} perPageOptions={perPageOptions}
          onPerPageChange={(n) => { perPage = n; currentPage = 1; fetchActivities(); }}/>
      {/if}
    </div>
  {/if}

  {#if activeView === 'table'}
    <!-- TABLE view: activities in table with actions -->
    <div class="mt-4 bg-white dark:bg-black shadow-md rounded-lg">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-neutral-900">
            <tr>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Tanggal Aktivitas</th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Nama Aktivitas</th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Project</th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Kategori</th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Jenis</th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Mitra</th>
              <th class="relative px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-black">
            {#each activities as activity (activity.id)}
              <tr>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {new Date(activity.activity_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                  <a href={`/activities/${activity.id}`} class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300" title="Detail">
                    {activity.name}
                  </a>
                  <br>
                  <span class="text-xs text-gray-500 dark:text-gray-400">From: {activity.from || '-'} | {activity.short_desc}</span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {activity.project?.name.substring(0, 25)}{activity.project?.name.length > 25 ? '...' : ''}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm">
                  <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    {activity.kategori}
                  </span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{activity.jenis}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {#if (activity.jenis === 'Vendor' || activity.jenis === 'Customer') && activity.mitra}
                    {activity.mitra?.nama.substring(0, 25)}{activity.mitra?.nama.length > 25 ? '...' : ''}
                  {:else}-{/if}
                </td>
                <td class="relative whitespace-nowrap px-3 py-4 text-left text-sm font-medium">
                  <div class="flex items-left space-x-2">
                    <button on:click={() => openDetailDrawer(activity)} class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" title="Detail">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      <span class="sr-only">Detail, {activity.name}</span>
                    </button>
                    {#if canUpdateActivity}
                      <button on:click|stopPropagation={() => openEditModal(activity)} title="Edit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span class="sr-only">Edit, {activity.name}</span>
                      </button>
                    {/if}
                    {#if canDeleteActivity}
                      <button on:click|stopPropagation={() => handleDelete(activity.id)} title="Delete" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        <span class="sr-only">Hapus, {activity.name}</span>
                      </button>
                    {/if}
                  </div>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
      {#if activities.length > 0}
        <Pagination currentPage={currentPage} lastPage={lastPage} onPageChange={goToPage} totalItems={totalActivities} itemsPerPage={perPage} perPageOptions={perPageOptions}
          onPerPageChange={(n) => { perPage = n; currentPage = 1; fetchActivities(); }}/>
      {/if}
    </div>
  {/if}
{/if}

<!-- Modals -->
<ActivityFormModal
  bind:show={showCreateModal}
  title="Form Aktivitas Baru"
  submitLabel="Tambah Aktivitas"
  idPrefix="create"
  {form}
  {projects}
  {vendors}
  {activityKategoriList}
  {activityJenisList}
  allowRemoveAttachment={false}
  onSubmit={handleSubmitCreate}
/>

{#if editingActivity}
  <ActivityFormModal
    bind:show={showEditModal}
    title="Edit Aktivitas"
    submitLabel="Update Aktivitas"
    idPrefix="edit"
    {form}
    {projects}
    {vendors}
    {activityKategoriList}
    {activityJenisList}
    allowRemoveAttachment={true}
    onSubmit={handleSubmitUpdate}
  />
{/if}

<!-- Detail Drawer -->
<Drawer bind:show={showDetailDrawer} title="Detail Activity" on:close={() => (showDetailDrawer = false)}>
  <ActivityDetail activity={selectedActivity} />
</Drawer>