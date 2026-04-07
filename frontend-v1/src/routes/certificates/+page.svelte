<script lang="ts">
  import { onMount } from 'svelte';
  import axiosClient from '$lib/axiosClient';
  import Drawer from '$lib/components/Drawer.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import CertificateDetail from '$lib/components/detail/CertificatesDetail.svelte';
  import CertificateFormModal from '$lib/components/form/CertificateFormModal.svelte';
  import { userPermissions } from '$lib/stores/permissions';

  /**
   * Certificate type definition. Each certificate has optional attachments array.
   * Attachments may include description and original_name when editing.
   */
  type Option = { id: number; name?: string; nama?: string; title?: string; no_seri?: string };
  type AttachmentItem = { id: number; name: string; description?: string; original_name?: string; url: string; size?: number };

  type Certificate = {
    id: number;
    name: string;
    no_certificate: string;
    project_id: number | '' | null;
    barang_certificate_id: number | '' | null;
    status: string;
    date_of_issue: string;
    date_of_expired: string;
    project?: { id: number; name: string } | null;
    barang_certificate?: { id: number; name: string } | null;
    attachments?: AttachmentItem[];
  };

  let statuses: string[] = [];

  // list state
  let items: Certificate[] = [];
  let projects: Option[] = [];
  let barangCertificates: Option[] = [];
  let filteredBarangCertificates: Option[] = [];
  let loading = true;
  let error = '';
  let search = '';
  let statusFilter: '' | Certificate['status'] = '';
  let dateFromFilter = '';
  let dateToFilter = '';
  let showDateFilter = false;
  let currentPage = 1;
  let lastPage = 1;
  let totalItems = 0;
  let perPage: number = 50;
  const perPageOptions = [10, 25, 50, 100];

  let sortBy: 'created' | 'date_of_issue' | 'date_of_expired' = 'created';
  let sortDir: 'desc' | 'asc' = 'desc';

  let dateSortField: 'date_of_issue' | 'date_of_expired' = 'date_of_issue';

  // modal state
  let showCreateModal = false;
  let showEditModal = false;
  let showDetailDrawer = false;
  let editingItem: Certificate | null = null;
  let selectedItem: Certificate | null = null;
  let activeView: 'table' | 'list' = 'table';
  const views: Array<'table' | 'list'> = ['table', 'list'];

  // Permissions
  let canCreateCertificate = false;
  let canUpdateCertificate = false;
  let canDeleteCertificate = false;
  $: {
    const perms = $userPermissions ?? [];
    canCreateCertificate = perms.includes('certificate-create');
    canUpdateCertificate = perms.includes('certificate-update');
    canDeleteCertificate = perms.includes('certificate-delete');
  }

  function handleViewKeydown(e: KeyboardEvent) {
    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
      e.preventDefault();
      let idx = views.indexOf(activeView);
      idx = e.key === 'ArrowRight' ? (idx + 1) % views.length : (idx - 1 + views.length) % views.length;
      activeView = views[idx];
    }
  }

  // === FORM STATE (multi-file) ===
  // The form supports editing existing attachments (names/descriptions) via existing_attachments array.
  let form: {
    name: string;
    no_certificate: string;
    project_id: number | '' | null;
    barang_certificate_id: number | '' | null;
    status: Certificate['status'] | '';
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

  // fetch dependencies for select options (projects and barang certificates)
  // Dependencies are now fetched alongside the list via form_dependencies

  // fetch barang certificates by project for dependent select
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

  // callback when project changes in form
  function handleProjectChange(projectId: number | '' | null) {
    if (projectId) {
      fetchBarangCertificatesByProject(Number(projectId));
      form.barang_certificate_id = '';
    } else {
      filteredBarangCertificates = [];
      form.barang_certificate_id = '';
    }
  }

  // fetch list of certificates with filters and pagination
  async function fetchList() {
    loading = true;
    error = '';
    try {
      const res = await axiosClient.get('/certificates', {
        params: {
          search,
          status: statusFilter,
          date_from: dateFromFilter,
          date_to: dateToFilter,
          page: currentPage,
          per_page: perPage,
          sort_by: sortBy,
          sort_dir: sortDir
        }
      });
      const root = res.data ?? {};
      items = root.data ?? [];
      
      const formDeps = root.form_dependencies ?? root.meta?.form_dependencies ?? {};
      if (formDeps.projects) projects = formDeps.projects;
      if (formDeps.barang_certificates) barangCertificates = formDeps.barang_certificates;
      if (formDeps.statuses) statuses = formDeps.statuses;
      if (formDeps.barang_options) filteredBarangCertificates = formDeps.barang_options;

      const pag = root.meta ?? root.pagination ?? {};
      currentPage = pag.current_page ?? 1;
      lastPage = pag.last_page ?? 1;
      totalItems = pag.total ?? items.length;
    } catch (err: any) {
      error = err?.response?.data?.message || 'Gagal memuat data.';
    } finally {
      loading = false;
    }
  }

  // mount lifecycle: fetch dependencies and list
  onMount(() => {
    fetchList();
    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
  });

  // filter & search handlers
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
  function toggleDateFilter() {
    showDateFilter = !showDateFilter;
  }
  function handleClickOutside(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest('.date-filter-dropdown') && !target.closest('.date-filter-button')) showDateFilter = false;
  }

  // open create modal: reset form and filtered barang options
  function openCreateModal() {
    if (!canCreateCertificate) {
      console.warn('User lacks certificate-create permission');
      return;
    }
    form = {
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
    filteredBarangCertificates = [];
    showCreateModal = true;
  }

  // open edit modal: map existing attachments including description and original_name
  function openEditModal(item: Certificate) {
    if (!canUpdateCertificate) {
      console.warn('User lacks certificate-update permission');
      return;
    }
    editingItem = { ...item };
    form = {
      name: item.name ?? '',
      no_certificate: item.no_certificate ?? '',
      project_id: item.project_id ?? '',
      barang_certificate_id: item.barang_certificate_id ?? '',
      status: item.status ?? '',
      date_of_issue: item.date_of_issue ? new Date(item.date_of_issue).toISOString().split('T')[0] : '',
      date_of_expired: item.date_of_expired ? new Date(item.date_of_expired).toISOString().split('T')[0] : '',
      attachments: [],
      attachment_names: [],
      attachment_descriptions: [],
      existing_attachments: Array.isArray(item.attachments)
        ? item.attachments.map((a) => ({
            id: a.id,
            name: a.name ?? 'Lampiran',
            description: (a as any).description ?? '',
            original_name: (a as any).original_name ?? a.name ?? '',
            url: a.url,
            size: a.size
          }))
        : [],
      removed_existing_ids: []
    };
    if (item.project_id) fetchBarangCertificatesByProject(Number(item.project_id));
    else filteredBarangCertificates = [];
    showEditModal = true;
  }

  // open detail drawer
  function openDetailDrawer(item: Certificate) {
    selectedItem = { ...item };
    showDetailDrawer = true;
  }

  // helper to append scalar to FormData
  function appendScalar(fd: FormData, key: string, val: any) {
    if (val === null || val === undefined || val === '') return;
    fd.append(key, String(val));
  }

  // build FormData for create/update
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
    (form.attachment_names || []).forEach((n, i) => { if (n != null) fd.append(`attachment_names[${i}]`, n); });
    (form.attachment_descriptions || []).forEach((d, i) => { if (d != null) fd.append(`attachment_descriptions[${i}]`, d); });
    // existing attachments edits
    (form.existing_attachments || []).forEach((att, i) => {
      fd.append(`existing_attachment_ids[${i}]`, String(att.id));
      fd.append(`existing_attachment_names[${i}]`, att.name);
      fd.append(`existing_attachment_descriptions[${i}]`, att.description ?? '');
    });
    // removed ids
    (form.removed_existing_ids || []).forEach((id) => fd.append('removed_existing_ids[]', String(id)));
    return fd;
  }

  // handle create submit
  async function handleSubmitCreate() {
    if (!canCreateCertificate) {
      console.warn('Create certificate blocked by permission');
      return;
    }
    try {
      const fd = buildFormData();
      await axiosClient.post('/certificates', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      alert('Data berhasil ditambahkan');
      showCreateModal = false;
      fetchList();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal menambahkan data.';
      alert('Error:\n' + messages);
    }
  }

  // handle update submit
  async function handleSubmitUpdate() {
    if (!canUpdateCertificate) {
      console.warn('Update certificate blocked by permission');
      return;
    }
    if (!editingItem?.id) return;
    try {
      const fd = buildFormData();
      fd.append('_method', 'PUT');
      await axiosClient.post(`/certificates/${editingItem.id}`, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      alert('Data berhasil diperbarui');
      showEditModal = false;
      fetchList();
    } catch (err: any) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : err.response?.data?.message || 'Gagal memperbarui data.';
      alert('Error:\n' + messages);
    }
  }

  // handle delete
  async function handleDelete(id: number) {
    if (!canDeleteCertificate) {
      console.warn('Delete certificate blocked by permission');
      return;
    }
    if (!confirm('Yakin ingin menghapus data ini?')) return;
    try {
      await axiosClient.delete(`/certificates/${id}`);
      alert('Data berhasil dihapus');
      fetchList();
    } catch (err: any) {
      alert('Gagal menghapus data: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
    }
  }

  // badge classes helper
  function getStatusBadgeClasses(status: string) {
    switch (status) {
      case 'Aktif':
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
      case 'Tidak Aktif':
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
      case 'Belum':
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
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
  <title>Daftar Sertifikat - Indogreen</title>
</svelte:head>

<!-- Toolbar & filters -->
<div class="flex flex-col sm:flex-row items-center justify-between mb-4 space-y-4 sm:space-y-0 sm:space-x-4">
  <div class="flex w-full sm:w-auto space-x-2">
    <select bind:value={statusFilter} on:change={handleFilterOrSearch}
      class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300
             dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700">
      <option value="">Status: Semua</option>
      {#each statuses as s}<option value={s}>{s}</option>{/each}
    </select>
  </div>
  <div class="w-full sm:w-auto flex-grow">
    <div class="relative w-full sm:w-auto">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
      </div>
      <input
        type="text" placeholder="Cari certificate..." bind:value={search} on:input={handleSearchDebounced}
        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white text-gray-900 placeholder-gray-500
               focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm
               dark:bg-neutral-900 dark:text-gray-100 dark:placeholder-gray-400 dark:border-gray-700" />
    </div>
  </div>
  <div class="flex space-x-2 w-full sm:w-auto">
    {#if canCreateCertificate}
      <button
        on:click={openCreateModal}
        class="px-4 py-2 w-full sm:w-auto border border-transparent text-sm font-medium rounded-md shadow-sm text-white
               bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
               dark:focus:ring-offset-gray-800">
        Tambah Sertif
      </button>
    {/if}
  </div>
</div>

<!-- Date filter button & dropdown -->
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
  <!-- Date filter -->
  <div class="relative">
    <button on:click={toggleDateFilter}
      class="date-filter-button px-3 py-2 rounded-md text-sm font-semibold border hover:bg-gray-50 flex items-center space-x-1 transition-colors
             bg-white border-gray-300 text-gray-900 dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700 dark:hover:bg-neutral-800
             focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-offset-2 dark:focus:ring-offset-gray-800"
      class:bg-indigo-50={dateFromFilter || dateToFilter}
      class:border-indigo-300={dateFromFilter || dateToFilter}
      class:text-indigo-700={dateFromFilter || dateToFilter}>
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span>Filter Tanggal</span>
      {#if dateFromFilter || dateToFilter}<div class="w-2 h-2 bg-indigo-500 rounded-full"></div>{/if}
      <svg class="w-4 h-4 transition-transform" class:rotate-180={showDateFilter} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    {#if showDateFilter}
      <div class="date-filter-dropdown absolute right-0 mt-2 w-80 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg z-10 p-4">
        <div class="space-y-3">
          <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Urutkan Berdasarkan Create
          </span>
          <select bind:value={sortDir} on:change={handleFilterOrSearch}
            class="w-full px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300
                  dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700">
            <option value="desc">Terbaru</option>
            <option value="asc">Terlama</option>
          </select>
          <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Urutkan Tanggal
          </span>
          <select
            bind:value={dateSortField}
            class="w-full mb-2 px-3 py-2 rounded-md text-sm border border-gray-300 dark:border-gray-700
                  bg-white text-gray-900 dark:bg-neutral-900 dark:text-gray-100"
            title="Pilih tanggal yang diurutkan"
          >
            <option value="date_of_issue">Tanggal Terbit</option>
            <option value="date_of_expired">Tanggal Expired</option>
          </select>

          <!-- Arah urutan -->
          <div class="inline-flex w-full rounded-md overflow-hidden border border-gray-300 dark:border-gray-700" role="tablist" aria-label="Urutan tanggal">
            <button
              type="button"
              on:click={() => { sortBy = dateSortField; sortDir = 'desc'; currentPage = 1; fetchList(); }}
              class="w-full px-3 py-1.5 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
              class:bg-indigo-600={sortBy===dateSortField && sortDir==='desc'}
              class:text-white={sortBy===dateSortField && sortDir==='desc'}
              class:bg-white={!(sortBy===dateSortField && sortDir==='desc')}
              class:text-gray-900={!(sortBy===dateSortField && sortDir==='desc')}
              class:dark:bg-neutral-900={!(sortBy===dateSortField && sortDir==='desc')}
              class:dark:text-gray-100={!(sortBy===dateSortField && sortDir==='desc')}
              aria-selected={sortBy===dateSortField && sortDir==='desc'}
              role="tab"
            >
              Terbaru dulu
            </button>
            <button
              type="button"
              on:click={() => { sortBy = dateSortField; sortDir = 'asc'; currentPage = 1; fetchList(); }}
              class="w-full px-3 py-1.5 text-sm font-semibold transition-colors border-l border-gray-300 dark:border-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
              class:bg-indigo-600={sortBy===dateSortField && sortDir==='asc'}
              class:text-white={sortBy===dateSortField && sortDir==='asc'}
              class:bg-white={!(sortBy===dateSortField && sortDir==='asc')}
              class:text-gray-900={!(sortBy===dateSortField && sortDir==='asc')}
              class:dark:bg-neutral-900={!(sortBy===dateSortField && sortDir==='asc')}
              class:dark:text-gray-100={!(sortBy===dateSortField && sortDir==='asc')}
              aria-selected={sortBy===dateSortField && sortDir==='asc'}
              role="tab"
            >
              Terlama dulu
            </button>
          </div>

          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Gunakan menu <b>Sortir</b> di toolbar untuk kembali ke urutan <b>Create</b>.
          </p>
          {#if dateFromFilter || dateToFilter}
            <div class="text-xs text-gray-500 dark:text-gray-300 bg-gray-50 dark:bg-neutral-800 p-2 rounded">
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
            <label for="filter_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal Terbit</label>
            <input id="filter_from" type="date" bind:value={dateFromFilter} on:change={handleFilterOrSearch}
              class="w-full px-3 py-2 rounded-md text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700" />
          </div>
          <div>
            <label for="filter_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal Terbit</label>
            <input id="filter_to" type="date" bind:value={dateToFilter} on:change={handleFilterOrSearch}
              class="w-full px-3 py-2 rounded-md text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700" />
          </div>
          <div class="flex space-x-2 pt-2">
            <button
              on:click={() => {
                dateFromFilter = '';
                dateToFilter = '';
                sortBy = 'created';
                sortDir = 'desc';
                currentPage = 1;
                fetchList();
              }}
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
    <!-- List view for certificates -->
    <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
      <ul class="divide-y divide-gray-200 dark:divide-gray-700">
        {#each items as item (item.id)}
          <li>
            <a href={`/certificates/${item.id}`} class="block hover:bg-gray-50 dark:hover:bg-neutral-950 px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">{item.name}</p>
                <div class="ml-2 flex-shrink-0 flex">
                  <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {getStatusBadgeClasses(item.status)}">{item.status}</span>
                </div>
              </div>
              <div class="mt-2 sm:flex sm:justify-between">
                <div class="sm:flex">
                  <p class="flex items-center text-sm text-gray-500 dark:text-gray-300">
                    Project: {item.project?.name || '-'} | Barang: {item.barang_certificate?.name || '-'} | No: {item.no_certificate}
                  </p>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-300 sm:mt-0">
                  <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                  {#if item.date_of_issue}<p>Terbit: {new Date(item.date_of_issue).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</p>
                  {:else}<p>Terbit: -</p>{/if}
                </div>
              </div>
            </a>
            <div class="flex justify-end px-4 py-2 sm:px-6 space-x-2">
              <button on:click|stopPropagation={() => openDetailDrawer(item)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-yellow-600 hover:bg-yellow-700">Detail</button>
              {#if canUpdateCertificate}
                <button on:click|stopPropagation={() => openEditModal(item)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">Edit</button>
              {/if}
              {#if canDeleteCertificate}
                <button on:click|stopPropagation={() => handleDelete(item.id)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800">Hapus</button>
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
    <!-- Table view for certificates -->
    <div class="mt-4 bg-white dark:bg-black shadow-md rounded-lg">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-neutral-900">
            <tr>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Nama</th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">No. Sertifikat</th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Barang Sertifikat</th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Terbit</th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Expired</th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-black">
            {#each items as item (item.id)}
              <tr>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                  <a href={`/certificates/${item.id}`} class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">{item.name}</a><br>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{item.project?.name || '-'}</span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{item.no_certificate}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{item.barang_certificate?.name || '-'}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm">
                  <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {getStatusBadgeClasses(item.status)}">{item.status}</span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {#if item.date_of_issue}
                    {new Date(item.date_of_issue).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                  {:else}
                    <span class="text-gray-500 dark:text-gray-400">-</span>
                  {/if}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {#if item.date_of_expired}
                    {new Date(item.date_of_expired).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                  {:else}
                    <span class="text-gray-500 dark:text-gray-400">-</span>
                  {/if}
                </td>
                <td class="relative whitespace-nowrap px-3 py-4 text-sm">
                  <div class="flex items-center space-x-2">
                    <button title="Detail" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" on:click={() => openDetailDrawer(item)}>
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                      <span class="sr-only">Detail, {item.name}</span>
                    </button>
                    {#if canUpdateCertificate}
                      <button title="Edit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" on:click={() => openEditModal(item)}>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span class="sr-only">Edit, {item.name}</span>
                      </button>
                    {/if}
                    {#if canDeleteCertificate}
                      <button title="Hapus" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" on:click={() => handleDelete(item.id)}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
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

<!-- Create modal -->
<CertificateFormModal
  bind:show={showCreateModal}
  title="Tambah Sertifikat"
  submitLabel="Simpan"
  idPrefix="create"
  {form}
  {projects}
  barangOptions={filteredBarangCertificates}
  statuses={statuses}
  handleProjectChange={handleProjectChange}
  onSubmit={handleSubmitCreate}
/>

<!-- Edit modal -->
{#if editingItem}
  <CertificateFormModal
    bind:show={showEditModal}
    title="Edit Sertifikat"
    submitLabel="Update"
    idPrefix="edit"
    {form}
    {projects}
    barangOptions={filteredBarangCertificates}
    statuses={statuses}
    handleProjectChange={handleProjectChange}
    onSubmit={handleSubmitUpdate}
  />
{/if}

<!-- Detail drawer -->
<Drawer bind:show={showDetailDrawer} title="Detail Sertifikat" on:close={() => (showDetailDrawer = false)}>
  <CertificateDetail certificates={selectedItem} />
</Drawer>