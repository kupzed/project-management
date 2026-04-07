<script lang="ts">
  import { page } from '$app/stores';
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import axios from 'axios';
  import axiosClient from '$lib/axiosClient';
  import Drawer from '$lib/components/Drawer.svelte';
  import ActivityDetail from '$lib/components/detail/ActivityDetail.svelte';
  import ProjectDetail from '$lib/components/detail/ProjectDetail.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import CertificatesDetail from '$lib/components/detail/CertificatesDetail.svelte';
  import CertificateFormModal from '$lib/components/form/CertificateFormModal.svelte';
  import ActivityFormModal from '$lib/components/form/ActivityFormModal.svelte';
  import ProjectFormModal from '$lib/components/form/ProjectFormModal.svelte';
  import { userPermissions } from '$lib/stores/permissions';

  let projectId: string | null = null;
  let project: any = null;
  let activities: any[] = [];
  let customers: any[] = [];
  let vendors: any[] = [];
  let loadingProject = true;
  let loadingActivities = true;
  let errorProject = '';
  let errorActivities = '';
  let activitiesInitialized = false;

  // Activity filter/search state
  let activityJenisFilter = '';
  let activityKategoriFilter = '';
  let activitySearch = '';
  let activityCurrentPage = 1;
  let activityLastPage = 1;
  let totalActivities = 0;
  let activityPerPage = 50;
  let certificatePerPage = 50;
  const perPageOptions = [10, 25, 50, 100];

  // Sorting (Activity)
  let activitySortBy: 'created' | 'activity_date' = 'activity_date';
  let activitySortDir: 'asc' | 'desc' = 'asc';

  // Date filter state
  let activityDateFromFilter = '';
  let activityDateToFilter = '';
  let showActivityDateFilter = false;

  // Vendor filter state
  let activityVendorFilter: number | string = '';
  let projectVendorOptions: Array<{ id: number; nama: string }> = [];

  // Project Edit Modal
  let showEditProjectModal = false;
  let editProjectForm = {
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

  // Activity Create Modal (multi-file)
  let showCreateActivityModal = false;
  let createActivityForm: {
    name: string;
    short_desc: string;
    description: string;
    project_id: number | string | '';
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
  };

  // Activity Edit Modal (multi-file)
  let showEditActivityModal = false;
  let editingActivity: any = null;
  let editActivityForm: {
    name: string;
    short_desc: string;
    description: string;
    project_id: number | string | '';
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
    existing_attachments: Array<{ id: number; name: string; url: string; size?: number; description?: string }>;
    removed_existing_ids: number[];
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

  // Drawer activity
  let showActivityDetailDrawer = false;
  let selectedActivity: any = null;

  // Lists dari backend
  let activityKategoriList: string[] = [];
  let activityJenisList: string[] = [];

  // Permissions
  let canUpdateProject = false;
  let canDeleteProject = false;
  let canCreateActivity = false;
  let canUpdateActivity = false;
  let canDeleteActivity = false;
  let canCreateCertificate = false;
  let canUpdateCertificate = false;
  let canDeleteCertificate = false;

  $: {
    const perms = $userPermissions ?? [];
    canUpdateProject = perms.includes('project-update');
    canDeleteProject = perms.includes('project-delete');
    canCreateActivity = perms.includes('activity-create');
    canUpdateActivity = perms.includes('activity-update');
    canDeleteActivity = perms.includes('activity-delete');
    canCreateCertificate = perms.includes('certificate-create');
    canUpdateCertificate = perms.includes('certificate-update');
    canDeleteCertificate = perms.includes('certificate-delete');
  }

  // === BADGE STATUS: konsisten dengan Dashboard (punya varian dark) ===
  function getStatusBadgeClasses(status: string) {
    switch (status) {
      case 'Complete': return 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
      case 'Ongoing': return 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200';
      case 'Prospect': return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200';
      case 'Cancel': return 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
      default: return 'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200';
    }
  }

  async function fetchProjectDetails() {
    loadingProject = true;
    errorProject = '';
    projectId = $page.params.id;
    if (!projectId) {
      errorProject = 'Project ID tidak ditemukan.';
      loadingProject = false;
      return;
    }
    try {
      const response = await axiosClient.get(`/projects/${projectId}`);
      const payload = response.data?.data ?? {};
      project = payload.project ?? payload;
      
      const deps = response.data?.form_dependencies;
      if (deps) {
        projectStatuses = Array.isArray(deps.project_status_list) ? deps.project_status_list : [];
        projectKategoris = Array.isArray(deps.project_kategori_list) ? deps.project_kategori_list : [];
        if (!customers?.length) {
          customers = Array.isArray(deps.customers) ? deps.customers : [];
        }
      }
      editProjectForm = {
        name: project.name,
        description: project.description,
        status: project.status,
        start_date: project.start_date ? new Date(project.start_date).toISOString().split('T')[0] : '',
        finish_date: project.finish_date ? new Date(project.finish_date).toISOString().split('T')[0] : '',
        mitra_id: project.mitra_id || '',
        kategori: project.kategori || '',
        lokasi: project.lokasi || '',
        no_po: project.no_po || '',
        no_so: project.no_so || '',
        is_cert_projects: project.is_cert_projects || false,
      };

      createActivityForm.project_id = project.id;
      if (project.mitra && project.mitra.is_customer) {
        createActivityForm.mitra_id = project.mitra.id;
        createActivityForm.jenis = 'Customer';
      } else {
        createActivityForm.jenis = '';
      }
    } catch (err: any) {
      errorProject = err.response?.data?.message || 'Gagal memuat proyek.';
      console.error('Error fetching project details:', err.response || err);
    } finally {
      loadingProject = false;
    }
  }

  async function fetchActivities() {
    loadingActivities = true;
    errorActivities = '';

    try {
      const pid = project?.id ?? $page.params.id;
      if (!pid) {
        errorActivities = 'Project ID tidak ditemukan.';
        return;
      }

      const res = await axiosClient.get('/activities', {
        params: {
          project_id: pid, // <<— kunci: filter berdasarkan project
          jenis: activityJenisFilter,
          kategori: activityKategoriFilter,
          // kirim mitra_id hanya kalau Jenis = Vendor dan user pilih sesuatu
          mitra_id: activityJenisFilter === 'Vendor' && activityVendorFilter ? activityVendorFilter : undefined,
          search: activitySearch,
          date_from: activityDateFromFilter,
          date_to: activityDateToFilter,
          page: activityCurrentPage,
          per_page: activityPerPage,
          // sorting
          sort_by: activitySortBy,
          sort_dir: activitySortDir,
        }
      });

      const root = res.data ?? {};
      const items = root.data ?? [];
      const pagination = root.meta ?? root.pagination ?? {};

      activities = Array.isArray(items) ? items : [];
      activityCurrentPage = pagination.current_page ?? root.current_page ?? 1;
      activityLastPage = pagination.last_page ?? root.last_page ?? 1;
      totalActivities =
        pagination.total ?? root.total ?? (Array.isArray(activities) ? activities.length : 0);

      // Vendor unik per project (buat dropdown "Vendor: ...")
      projectVendorOptions = Array.isArray(root.vendor_options) ? root.vendor_options : [];

      if (root.form_dependencies) {
        const dep = root.form_dependencies;
        vendors = Array.isArray(dep.vendors) ? dep.vendors : [];
        if (Array.isArray(dep.customers) && dep.customers.length > 0) {
          customers = dep.customers;
        }
        activityKategoriList = Array.isArray(dep.kategori_list) ? dep.kategori_list : [];
        activityJenisList = Array.isArray(dep.jenis_list) ? dep.jenis_list : [];
      }
    } catch (err: unknown) {
      if (axios.isAxiosError(err)) {
        errorActivities =
          (err.response?.data as any)?.message ||
          err.message ||
          'Gagal memuat aktivitas.';
      } else {
        errorActivities =
          (err as Error).message || 'Gagal memuat aktivitas.';
      }
    } finally {
      loadingActivities = false;
    }
  }



  function goToActivityPage(page: number) {
    if (page > 0 && page <= activityLastPage) {
      activityCurrentPage = page;
      fetchActivities();
    }
  }
  function handleActivityFilterOrSearch() { activityCurrentPage = 1; fetchActivities(); }

  let searchTimer: ReturnType<typeof setTimeout>;

  function handleSearchDebounced() {
    clearTimeout(searchTimer);
    
    searchTimer = setTimeout(() => {
      handleActivityFilterOrSearch();
      handleCertificateSearchChange();
    }, 800);
  }

  function clearActivityFilters() {
    activityJenisFilter = '';
    activityKategoriFilter = '';
    activityVendorFilter = '';
    activitySearch = '';
    activityDateFromFilter = '';
    activityDateToFilter = '';
    activitySortBy = 'activity_date';
    activitySortDir = 'asc';
    activityCurrentPage = 1;
    fetchActivities();
  }
  function toggleActivityDateFilter() { showActivityDateFilter = !showActivityDateFilter; }
  function handleActivityDateFilter() { activityCurrentPage = 1; fetchActivities(); }

  function handleClickOutside(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest('.date-filter-dropdown')) showActivityDateFilter = false;
    if (!target.closest('.certificate-date-filter-dropdown') && !target.closest('.certificate-date-filter-button')) {
      showCertificateDateFilter = false;
    }
  }

  onMount(() => {
    fetchProjectDetails();
    
    if (activeTab === 'activity') {
      activitiesInitialized = true;
      fetchActivities();
    }
    if (activeTab === 'certificates') {
      certificatesInitialized = true;
      fetchCertificates();
    }
    
    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
  });

  function openEditProjectModal() {
    if (!canUpdateProject) {
      console.warn('User lacks project-update permission');
      return;
    }
    showEditProjectModal = true;
  }
  async function handleSubmitUpdateProject() {
    if (!project?.id) return;
    if (!canUpdateProject) {
      console.warn('User lacks project-update permission');
      return;
    }
    try {
      await axiosClient.put(`/projects/${project.id}`, editProjectForm);
      alert('Proyek berhasil diperbarui!');
      goto(`/projects/${project.id}`);
      showEditProjectModal = false;
      fetchProjectDetails();
    } catch (err: any) {
      const messages = err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join('\n') : err.response?.data?.message || 'Gagal memperbarui proyek.';
      alert('Error:\n' + messages);
    }
  }
  async function handleDeleteProject() {
    if (!project?.id) return;
    if (!canDeleteProject) {
      console.warn('Delete attempt blocked: no permission');
      return;
    }
    if (!confirm('Apakah Anda yakin ingin menghapus proyek ini?')) return;
    try {
      await axiosClient.delete(`/projects/${project.id}`);
      alert('Proyek berhasil dihapus!');
      goto('/projects');
    } catch (err: any) {
      alert('Gagal menghapus proyek: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
    }
  }

  function openCreateActivityModal() {
    if (!canCreateActivity) {
      console.warn('User lacks activity-create permission');
      return;
    }
    createActivityForm = {
      name: '',
      short_desc: '',
      description: '',
      project_id: project.id,
      kategori: '',
      value: 0,
      activity_date: '',
      jenis: project.mitra && project.mitra.is_customer ? 'Customer' : '',
      mitra_id: project.mitra && project.mitra.is_customer ? project.mitra.id : null,
      from: '',
      to: '',
      attachments: [],
      attachment_names: [],
      attachment_descriptions: [],
    };
    showCreateActivityModal = true;
  }

  function appendScalar(fd: FormData, key: string, val: any) {
    if (val === null || val === undefined || val === '') return;
    fd.append(key, String(val));
  }

  function buildActivityFormData(data: {
    name: string; short_desc: string; description: string; project_id: number | string | '';
    kategori: string | ''; value:number; activity_date: string | ''; jenis: string | '';
    mitra_id: number | string | '' | null; from?: string | ''; to?: string | '';
    attachments: File[]; attachment_names: string[]; attachment_descriptions: string[];
    removed_existing_ids?: number[];
    existing_attachments?: Array<{ id: number; name: string; url: string; size?: number; description?: string }>;
  }) {
    const fd = new FormData();
    appendScalar(fd, 'name', data.name);
    appendScalar(fd, 'short_desc', data.short_desc);
    appendScalar(fd, 'description', data.description);
    appendScalar(fd, 'project_id', data.project_id);
    appendScalar(fd, 'kategori', data.kategori);
    appendScalar(fd, 'value', data.value);
    appendScalar(fd, 'activity_date', data.activity_date);
    appendScalar(fd, 'jenis', data.jenis);
    appendScalar(fd, 'from', data.from);
    appendScalar(fd, 'to', data.to);

    // mitra_id rules
    if (data.jenis === 'Internal') {
      fd.set('mitra_id', '1');
    } else if (data.jenis === 'Customer') {
      if (project?.mitra_id) fd.set('mitra_id', String(project.mitra_id));
    } else if (data.jenis === 'Vendor' && data.mitra_id) {
      fd.set('mitra_id', String(data.mitra_id));
    }

    (data.attachments || []).forEach((file, i) => fd.append(`attachments[${i}]`, file));
    (data.attachment_names || []).forEach((n, i) => { if (n != null) fd.append(`attachment_names[${i}]`, n); });
    (data.attachment_descriptions || []).forEach((d, i) => { if (d != null) fd.append(`attachment_descriptions[${i}]`, d); });
    (data.removed_existing_ids || []).forEach((id) => fd.append('removed_existing_ids[]', String(id)));

    const existing = (data.existing_attachments ?? []) as Array<{ id: number; name: string; description?: string }>;
    if (existing.length) {
      existing.forEach((att, i) => {
        fd.append(`existing_attachment_ids[${i}]`, String(att.id));
        fd.append(`existing_attachment_names[${i}]`, att.name ?? '');
        fd.append(`existing_attachment_descriptions[${i}]`, att.description ?? '');
      });
    }

    return fd;
  }

  async function handleSubmitCreateActivity() {
    if (!canCreateActivity) {
      console.warn('Create activity blocked by permission');
      return;
    }
    try {
      const fd = buildActivityFormData(createActivityForm);
      await axiosClient.post('/activities', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      alert('Aktivitas berhasil ditambahkan!');
      goto(`/projects/${project.id}`);
      showCreateActivityModal = false;
      fetchActivities();
    } catch (err: any) {
      const messages = err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join('\n') : err.response?.data?.message || 'Gagal menambahkan aktivitas.';
      alert('Error:\n' + messages);
    }
  }

  function openEditActivityModal(activity: any) {
    if (!canUpdateActivity) {
      console.warn('User lacks activity-update permission');
      return;
    }
    editingActivity = { ...activity, activity_date: activity.activity_date ? new Date(activity.activity_date).toISOString().split('T')[0] : '' };
    editActivityForm = {
      name: editingActivity.name ?? '',
      short_desc: editingActivity.short_desc ?? '',
      description: editingActivity.description ?? '',
      project_id: editingActivity.project_id || '',
      kategori: editingActivity.kategori || '',
      value: editingActivity.value || 0,
      activity_date: editingActivity.activity_date || '',
      jenis: editingActivity.jenis || '',
      mitra_id: editingActivity.mitra_id || '',
      from: editingActivity.from || '',
      to: editingActivity.to || '',
      attachments: [],
      attachment_names: [],
      attachment_descriptions: [],
      existing_attachments: Array.isArray(editingActivity.attachments)
        ? editingActivity.attachments.map((a: any) => ({
            id: a.id,
            name: a.name ?? a.file_name ?? 'Lampiran',
            url: a.url ?? a.path ?? a.file_path,
            size: a.size,
            description: a.description ?? '',
            original_name: a.original_name ?? a.file_name ?? a.name ?? ''
          }))
        : [],
      removed_existing_ids: []
    };
    showEditActivityModal = true;
  }

  function openActivityDetailDrawer(activity: any) { selectedActivity = { ...activity }; showActivityDetailDrawer = true; }

  async function handleSubmitUpdateActivity() {
    if (!canUpdateActivity) {
      console.warn('Update activity blocked by permission');
      return;
    }
    if (!editingActivity?.id) return;
    try {
      const fd = buildActivityFormData(editActivityForm);
      fd.append('_method', 'PUT');
      await axiosClient.post(`/activities/${editingActivity.id}`, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      alert('Aktivitas berhasil diperbarui!');
      goto(`/projects/${project.id}`);
      showEditActivityModal = false;
      fetchActivities();
    } catch (err: any) {
      const messages = err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join('\n') : err.response?.data?.message || 'Gagal memperbarui aktivitas.';
      alert('Error:\n' + messages);
    }
  }

  async function handleDeleteActivity(activityId: number) {
    if (!canDeleteActivity) {
      console.warn('Delete activity blocked by permission');
      return;
    }
    if (!confirm('Apakah Anda yakin ingin menghapus aktivitas ini?')) return;
    try {
      await axiosClient.delete(`/activities/${activityId}`);
      alert('Aktivitas berhasil dihapus!');
      goto(`/projects/${project.id}`);
      fetchActivities();
    } catch (err: any) {
      alert('Gagal menghapus aktivitas: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
    }
  }

  let previousCreateActivityJenis = '';
  $: if (showCreateActivityModal && createActivityForm.jenis && createActivityForm.jenis !== previousCreateActivityJenis) {
    previousCreateActivityJenis = createActivityForm.jenis;
    if (createActivityForm.jenis === 'Customer')      createActivityForm.mitra_id = project?.mitra_id || null;
    else if (createActivityForm.jenis === 'Internal') createActivityForm.mitra_id = '1';
    else if (createActivityForm.jenis === 'Vendor')   { if (!Array.isArray(vendors) || !vendors.some(v => v.id == createActivityForm.mitra_id)) createActivityForm.mitra_id = ''; }
    else                                              createActivityForm.mitra_id = '';
  }
  $: if (!showCreateActivityModal) { createActivityForm.mitra_id = ''; createActivityForm.jenis = ''; previousCreateActivityJenis = ''; }

  // Vendor filter
  $: if (activityJenisFilter !== 'Vendor') {
    activityVendorFilter = '';
  }

  // Tabs
  let activeTab: 'detail' | 'activity' | 'certificates' = 'activity';
  let activityView: 'table' | 'list' = 'table';
  const activityViews: Array<'table' | 'list'> = ['table', 'list'];

  function handleActivityViewKeydown(e: KeyboardEvent) {
    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
      e.preventDefault();
      let idx = activityViews.indexOf(activityView);
      idx = e.key === 'ArrowRight' ? (idx + 1) % activityViews.length : (idx - 1 + activityViews.length) % activityViews.length;
      activityView = activityViews[idx];
    }
  }

  // Certificates
  type Option = { id: number; name?: string; nama?: string; title?: string; no_seri?: string };
  type ProjectCertificate = {
    id: number; name: string; no_certificate: string;
    status: string;
    date_of_issue: string; date_of_expired: string;
    attachments?: Array<{ id: number; name: string; url: string; size?: number }>;
    barang_certificate?: { id: number; name: string } | null;
  };
  let certificates: ProjectCertificate[] = [];
  let loadingCertificates = false;
  let errorCertificates = '';
  let certificateSearch = '';
  let certificateStatusFilter: '' | ProjectCertificate['status'] = '';
  // === Sorting (Certificates) ===
  let certificateSortBy: 'created' | 'date_of_issue' | 'date_of_expired' = 'created'; // default create
  let certificateSortDir: 'asc' | 'desc' = 'desc';                                     // default terbaru
  let certificateDateSortField: 'date_of_issue' | 'date_of_expired' = 'date_of_issue'; // dropdown tanggal pilih field
  let certificateCurrentPage = 1;
  let certificateLastPage = 1;
  let totalCertificates = 0;
  let certificatesInitialized = false;
  let certificateDependenciesInitialized = false;
  let certificateStatuses: string[] = [];
  let certificateBarangOptions: Option[] = [];

  function getCertificateStatusBadgeClasses(status: string) {
    switch (status) {
      case 'Aktif': return 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
      case 'Tidak Aktif': return 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
      case 'Belum': return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200';
      default: return 'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200';
    }
  }

  async function fetchCertificates() {
    if (!project?.id) return;
    loadingCertificates = true;
    errorCertificates = '';
    try {
      const res = await axiosClient.get('/certificates', {
        params: {
          project_id: project.id,
          search: certificateSearch,
          status: certificateStatusFilter,
          date_from: certificateDateFromFilter,
          date_to: certificateDateToFilter,
          page: certificateCurrentPage,
          per_page: certificatePerPage,
          sort_by: certificateSortBy,
          sort_dir: certificateSortDir,
        },
      });

      const root = res.data ?? {};
      certificates = root.data ?? [];
      
      const formDeps = root.form_dependencies ?? {};
      if (formDeps.barang_options) {
        certificateBarangOptions = formDeps.barang_options;
      }
      if (formDeps.statuses && !certificateDependenciesInitialized) {
        certificateStatuses = formDeps.statuses;
        certificateDependenciesInitialized = true;
      }

      const pag = root.meta ?? root.pagination ?? {};
      certificateCurrentPage = pag.current_page ?? 1;
      certificateLastPage = pag.last_page ?? 1;
      totalCertificates = pag.total ?? certificates.length;
    } catch (err: any) {
      errorCertificates = err?.response?.data?.message || 'Gagal memuat sertifikat.';
    } finally { loadingCertificates = false; }
  }
  function handleCertificateSearchChange() { certificateCurrentPage = 1; fetchCertificates(); }
  function goToCertificatePage(page: number) { if (page > 0 && page <= certificateLastPage) { certificateCurrentPage = page; fetchCertificates(); } }
  function handleCertificateFilterOrSearch() { certificateCurrentPage = 1; fetchCertificates(); }
  let certificateView: 'table' | 'list' = 'table';
  const certificateViews: Array<'table' | 'list'> = ['table', 'list'];

  function handleCertificateViewKeydown(e: KeyboardEvent) {
    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
      e.preventDefault();
      let idx = certificateViews.indexOf(certificateView);
      idx = e.key === 'ArrowRight' ? (idx + 1) % certificateViews.length : (idx - 1 + certificateViews.length) % certificateViews.length;
      certificateView = certificateViews[idx];
    }
  }
  let certificateDateFromFilter = '';
  let certificateDateToFilter = '';
  let showCertificateDateFilter = false;
  function toggleCertificateDateFilter() { showCertificateDateFilter = !showCertificateDateFilter; }
  function handleCertificateDateFilter() { certificateCurrentPage = 1; fetchCertificates(); }

  $: if (activeTab === 'activity' && project?.id && !activitiesInitialized) { activitiesInitialized = true; fetchActivities(); }
  $: if (activeTab === 'certificates' && project && !project.is_cert_projects) { activeTab = 'detail'; }
  $: if (activeTab === 'certificates' && project?.id && !certificatesInitialized) {
    certificatesInitialized = true; 
    fetchCertificates();
  }

  // Certificate create/edit forms (multi-file)
  let certificateForm: {
    name: string;
    no_certificate: string;
    project_id: number | string | '';
    barang_certificate_id: number | string | '' | null;
    status: string | '';
    date_of_issue: string;
    date_of_expired: string;
    attachments: File[];
    attachment_names: string[];
    attachment_descriptions: string[];
    existing_attachments?: Array<{ id: number; name: string; url: string; size?: number; description?: string }>;
    removed_existing_ids?: number[];
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
  let showCreateCertificateModal = false;
  let showEditCertificateModal = false;
  let showCertificateDetailDrawer = false;
  let editingCertificate: ProjectCertificate | null = null;
  let selectedCertificate: ProjectCertificate | null = null;

  function openCreateCertificateModal() {
    if (!canCreateCertificate) {
      console.warn('User lacks certificate-create permission');
      return;
    }
    certificateForm = {
      name: '', no_certificate: '', project_id: project?.id ?? '',
      barang_certificate_id: '', status: '',
      date_of_issue: '', date_of_expired: '',
      attachments: [], attachment_names: [], attachment_descriptions: [],
      existing_attachments: [], removed_existing_ids: []
    };
    showCreateCertificateModal = true;
  }
  function openEditCertificateModal(item: ProjectCertificate) {
    if (!canUpdateCertificate) {
      console.warn('User lacks certificate-update permission');
      return;
    }
    editingCertificate = { ...item };
    certificateForm = {
      name: item.name ?? '', no_certificate: item.no_certificate ?? '', project_id: project?.id ?? '',
      barang_certificate_id: item.barang_certificate?.id ?? '',
      status: item.status ?? '',
      date_of_issue: item.date_of_issue ? new Date(item.date_of_issue).toISOString().split('T')[0] : '',
      date_of_expired: item.date_of_expired ? new Date(item.date_of_expired).toISOString().split('T')[0] : '',
      attachments: [], attachment_names: [], attachment_descriptions: [],
      existing_attachments: Array.isArray(item.attachments)
        ? item.attachments.map((a: any) => ({
            id: a.id,
            name: a.name,
            url: a.url ?? a.path ?? a.file_path,
            size: a.size,
            description: a.description ?? '',
            original_name: a.original_name ?? a.file_name ?? a.name ?? ''
          }))
        : [],
      removed_existing_ids: []
    };
    showEditCertificateModal = true;
  }
  function openCertificateDetailDrawer(item: ProjectCertificate) { selectedCertificate = { ...item }; showCertificateDetailDrawer = true; }

  function buildCertificateFormData() {
    const fd = new FormData();
    if (project?.id) fd.append('project_id', String(project.id));
    fd.append('name', certificateForm.name);
    fd.append('no_certificate', certificateForm.no_certificate);
    if (certificateForm.barang_certificate_id !== '' && certificateForm.barang_certificate_id !== null) fd.append('barang_certificate_id', String(certificateForm.barang_certificate_id));
    if (certificateForm.status) fd.append('status', certificateForm.status);
    fd.append('date_of_issue', certificateForm.date_of_issue || '');
    fd.append('date_of_expired', certificateForm.date_of_expired || '');

    (certificateForm.attachments || []).forEach((file, i) => fd.append(`attachments[${i}]`, file));
    (certificateForm.attachment_names || []).forEach((n, i) => { if (n != null) fd.append(`attachment_names[${i}]`, n); });
    (certificateForm.attachment_descriptions || []).forEach((d, i) => { if (d != null) fd.append(`attachment_descriptions[${i}]`, d); });
    (certificateForm.removed_existing_ids || []).forEach((id) => fd.append('removed_existing_ids[]', String(id)));

    const existing = (certificateForm.existing_attachments ?? []) as Array<{ id: number; name: string; description?: string }>;
    if (existing.length) {
      existing.forEach((att, i) => {
        fd.append(`existing_attachment_ids[${i}]`, String(att.id));
        fd.append(`existing_attachment_names[${i}]`, att.name ?? '');
        fd.append(`existing_attachment_descriptions[${i}]`, att.description ?? '');
      });
    }

    return fd;
  }

  async function handleSubmitCreateCertificate() {
    if (!canCreateCertificate) {
      console.warn('Create certificate blocked by permission');
      return;
    }
    try {
      const fd = buildCertificateFormData();
      await axiosClient.post('/certificates', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      alert('Certificate berhasil ditambahkan'); showCreateCertificateModal = false; fetchCertificates();
    } catch (err: any) {
      const messages = err.response?.data?.errors ? (Object.values(err.response.data.errors) as any).flat().join('\n') : err.response?.data?.message || 'Gagal menambahkan data.';
      alert('Error:\n' + messages);
    }
  }
  async function handleSubmitUpdateCertificate() {
    if (!canUpdateCertificate) {
      console.warn('Update certificate blocked by permission');
      return;
    }
    if (!editingCertificate?.id) return;
    try {
      const fd = buildCertificateFormData(); fd.append('_method','PUT');
      await axiosClient.post(`/certificates/${editingCertificate.id}`, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      alert('Certificate berhasil diperbarui'); showEditCertificateModal = false; fetchCertificates();
    } catch (err: any) {
      const messages = err.response?.data?.errors ? (Object.values(err.response.data.errors) as any).flat().join('\n') : err.response?.data?.message || 'Gagal memperbarui data.';
      alert('Error:\n' + messages);
    }
  }
  async function handleDeleteCertificate(id: number) {
    if (!canDeleteCertificate) {
      console.warn('Delete certificate blocked by permission');
      return;
    }
    if (!confirm('Yakin ingin menghapus certificate ini?')) return;
    try { await axiosClient.delete(`/certificates/${id}`); alert('Certificate berhasil dihapus'); fetchCertificates(); }
    catch (err: any) { alert('Gagal menghapus data: ' + (err.response?.data?.message || 'Terjadi kesalahan')); }
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
  $: lockBodyScroll(showEditProjectModal || showActivityDetailDrawer || 
  showCreateActivityModal || showEditActivityModal || showCertificateDetailDrawer ||
  showCreateCertificateModal || showEditCertificateModal);
</script>

<svelte:head>
  <title>Detail Project - Indogreen</title>
</svelte:head>

{#if loadingProject}
  <p class="text-gray-900 dark:text-white">Memuat proyek...</p>
{:else if errorProject}
  <p class="text-red-500">{errorProject}</p>
{:else if project}
  <div class="max-w-1xl mx-auto mb-8">
    <div class="flex justify-between items-center mb-4">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-2xl">
          {project.name}
        </h2>
        <div class="my-2 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
          <div class="my-2 flex items-center text-sm text-gray-500 dark:text-gray-300">
            <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
            </svg>
            Mulai: {new Date(project.start_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}
          </div>
          <div class="my-2 flex items-center text-sm">
            <span class={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${getStatusBadgeClasses(project.status)}`}>
              {project.status}
            </span>
          </div>
        </div>
      </div>
      <div class="flex flex-col md:flex-row mt-2 mb-4 md:mt-0 md:ml-4 md:mb-4 space-y-2 md:space-y-0 md:space-x-4">
        {#if canUpdateProject}
          <button
            on:click={openEditProjectModal}
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white
                  bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
            Edit Project
          </button>
        {/if}
        {#if canDeleteProject}
          <button
            on:click={handleDeleteProject}
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white
                  bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800">
            Hapus Project
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
          on:click={() => (activeTab = 'activity')}
          class="px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-200 text-gray-700 dark:text-gray-200"
          class:bg-white={activeTab === 'activity'}
          class:dark:bg-neutral-900={activeTab === 'activity'}
          class:shadow={activeTab === 'activity'}
          role="tab"
          aria-selected={activeTab === 'activity'}
        >
          Activity
        </button>
        {#if project?.is_cert_projects}
          <button
            on:click={() => (activeTab = 'certificates')}
            class="px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-200 text-gray-700 dark:text-gray-200"
            class:bg-white={activeTab === 'certificates'}
            class:dark:bg-neutral-900={activeTab === 'certificates'}
            class:shadow={activeTab === 'certificates'}
          >
            Certificate
          </button>
        {/if}
      </div>
    </div>

    <!-- DETAIL -->
    {#if activeTab === 'detail'}
      <div class="bg-white dark:bg-black shadow overflow-hidden mb-8">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Informasi Project</h3>
        </div>
        <div class="border-t border-gray-100 dark:border-gray-700">
          <ProjectDetail project={project} />
        </div>
      </div>
    {/if}

    <!-- ACTIVITY -->
    {#if activeTab === 'activity'}
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row items-center justify-between mb-4 space-y-4 sm:space-y-0 sm:space-x-4">
          <div class="flex w-full sm:w-auto space-x-2">
            <select bind:value={activityJenisFilter} on:change={handleActivityFilterOrSearch}
              class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300
                     dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700">
              <option value="">Jenis: Semua</option>
              {#each activityJenisList as jenis}<option value={jenis}>{jenis}</option>{/each}
            </select>
            {#if activityJenisFilter === 'Vendor'}
              <select
                bind:value={activityVendorFilter}
                on:change={handleActivityFilterOrSearch}
                class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300
                      dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700"
              >
                <option value="">Vendor: Semua</option>
                {#each projectVendorOptions as v}
                  <option value={v.id}>{v.nama}</option>
                {/each}
              </select>
            {/if}
            <select bind:value={activityKategoriFilter} on:change={handleActivityFilterOrSearch}
              class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300
                     dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700">
              <option value="">Kategori: Semua</option>
              {#each activityKategoriList as kategori}<option value={kategori}>{kategori}</option>{/each}
            </select>
          </div>

          <div class="w-full sm:w-auto flex-grow">
            <div class="relative w-full sm:w-auto">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
              </div>
              <input
                type="text" placeholder="Cari aktivitas..." bind:value={activitySearch} on:input={handleSearchDebounced}
                class="block w-full pl-10 pr-3 py-2 border rounded-md leading-5 bg-white text-gray-900 placeholder-gray-500 border-gray-300
                       focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm
                       dark:bg-neutral-900 dark:text-gray-100 dark:placeholder-gray-400 dark:border-gray-700"
              />
            </div>
          </div>

          {#if canCreateActivity}
            <button
              on:click={openCreateActivityModal}
              class="px-4 py-2 w-full sm:w-auto border border-transparent text-sm font-medium rounded-md shadow-sm text-white
                     bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                     dark:focus:ring-offset-gray-800">
              Tambah Aktivitas
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
            on:keydown={handleActivityViewKeydown}
          >
            <!-- TABLE -->
            <button
              on:click={() => (activityView = 'table')}
              class="grid h-9 w-9 place-items-center rounded-md
                    text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-50
                    focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:focus-visible:ring-gray-500"
              class:bg-white={activityView === 'table'}
              class:dark:bg-neutral-900={activityView === 'table'}
              class:text-gray-900={activityView === 'table'}
              class:dark:text-white={activityView === 'table'}
              class:border={activityView === 'table'}
              class:border-gray-300={activityView === 'table'}
              class:dark:border-gray-700={activityView === 'table'}
              role="tab"
              aria-selected={activityView === 'table'}
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
              on:click={() => (activityView = 'list')}
              class="grid h-9 w-9 place-items-center rounded-md
                    text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-50
                    focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:focus-visible:ring-gray-500"
              class:bg-white={activityView === 'list'}
              class:dark:bg-neutral-900={activityView === 'list'}
              class:text-gray-900={activityView === 'list'}
              class:dark:text-white={activityView === 'list'}
              class:border={activityView === 'list'}
              class:border-gray-300={activityView === 'list'}
              class:dark:border-gray-700={activityView === 'list'}
              role="tab"
              aria-selected={activityView === 'list'}
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

          <!-- Date Filter -->
          <div class="relative date-filter-dropdown">
            <button
              on:click={toggleActivityDateFilter}
              class="inline-flex items-center px-3 py-2 border rounded-md shadow-sm text-sm font-medium
                     text-gray-700 bg-white hover:bg-gray-50 border-gray-300
                     focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                     dark:text-gray-100 dark:bg-neutral-900 dark:border-gray-700 dark:hover:bg-neutral-800 dark:focus:ring-offset-gray-800"
              class:bg-indigo-50={activityDateFromFilter || activityDateToFilter}
              class:border-indigo-300={activityDateFromFilter || activityDateToFilter}
              class:text-indigo-700={activityDateFromFilter || activityDateToFilter}
            >
              <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              Filter Tanggal
              {#if activityDateFromFilter || activityDateToFilter}
                <div class="w-2 h-2 bg-indigo-600 rounded-full ml-2"></div>
              {/if}
              <svg class="w-4 h-4 transition-transform" class:rotate-180={showActivityDateFilter} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            {#if showActivityDateFilter}
              <div class="absolute right-0 mt-2 w-80 bg-white dark:bg-neutral-900 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                <div class="space-y-3 p-4">
                  <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Filter Tanggal Aktivitas</h3>
                    <button on:click={toggleActivityDateFilter} class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Tutup">
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                  </div>
                  <span class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                    Urutkan Berdasarkan Create
                  </span>
                  <div class="inline-flex w-full rounded-md overflow-hidden border border-gray-300 dark:border-gray-700" role="tablist" aria-label="Urutan tanggal aktivitas">
                    <select
                      bind:value={activitySortDir}
                      on:change={() => { activitySortBy = 'created'; handleActivityFilterOrSearch(); }}
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
                      on:click={() => { activitySortBy='activity_date'; activitySortDir='desc'; activityCurrentPage=1; fetchActivities(); }}
                      class="w-full px-3 py-1.5 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                      class:bg-indigo-600={activitySortBy==='activity_date' && activitySortDir==='desc'}
                      class:text-white={activitySortBy==='activity_date' && activitySortDir==='desc'}
                      class:bg-white={!(activitySortBy==='activity_date' && activitySortDir==='desc')}
                      class:text-gray-900={!(activitySortBy==='activity_date' && activitySortDir==='desc')}
                      class:dark:bg-neutral-900={!(activitySortBy==='activity_date' && activitySortDir==='desc')}
                      class:dark:text-gray-100={!(activitySortBy==='activity_date' && activitySortDir==='desc')}
                      aria-selected={activitySortBy==='activity_date' && activitySortDir==='desc'}
                      role="tab"
                    >
                      Terbaru dulu
                    </button>
                    <button
                      type="button"
                      on:click={() => { activitySortBy='activity_date'; activitySortDir='asc'; activityCurrentPage=1; fetchActivities(); }}
                      class="w-full px-3 py-1.5 text-sm font-semibold transition-colors border-l border-gray-300 dark:border-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                      class:bg-indigo-600={activitySortBy==='activity_date' && activitySortDir==='asc'}
                      class:text-white={activitySortBy==='activity_date' && activitySortDir==='asc'}
                      class:bg-white={!(activitySortBy==='activity_date' && activitySortDir==='asc')}
                      class:text-gray-900={!(activitySortBy==='activity_date' && activitySortDir==='asc')}
                      class:dark:bg-neutral-900={!(activitySortBy==='activity_date' && activitySortDir==='asc')}
                      class:dark:text-gray-100={!(activitySortBy==='activity_date' && activitySortDir==='asc')}
                      aria-selected={activitySortBy==='activity_date' && activitySortDir==='asc'}
                      role="tab"
                    >
                      Terlama dulu
                    </button>
                  </div>
                  <div>
                    <!-- svelte-ignore a11y_label_has_associated_control -->
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal</label>
                    <input type="date" bind:value={activityDateFromFilter} on:change={handleActivityDateFilter}
                      class="w-full px-3 py-2 border rounded-md text-sm border-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500
                             dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700" />
                  </div>
                  <div>
                    <!-- svelte-ignore a11y_label_has_associated_control -->
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal</label>
                    <input type="date" bind:value={activityDateToFilter} on:change={handleActivityDateFilter}
                      class="w-full px-3 py-2 border rounded-md text-sm border-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500
                             dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700" />
                  </div>

                  {#if activityDateFromFilter || activityDateToFilter}
                    <div class="text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-neutral-800 p-2 rounded">
                      <strong>Filter Aktif:</strong><br>
                      {#if activityDateFromFilter && activityDateToFilter}
                        {new Date(activityDateFromFilter).toLocaleDateString('id-ID')} - {new Date(activityDateToFilter).toLocaleDateString('id-ID')}
                      {:else if activityDateFromFilter}
                        Dari {new Date(activityDateFromFilter).toLocaleDateString('id-ID')}
                      {:else if activityDateToFilter}
                        Sampai {new Date(activityDateToFilter).toLocaleDateString('id-ID')}
                      {/if}
                    </div>
                  {/if}

                  <div class="flex justify-between mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" on:click={clearActivityFilters}
                      class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200
                             dark:text-gray-200 dark:bg-neutral-800 dark:border-gray-700 dark:hover:bg-neutral-700">
                      Clear All
                    </button>
                    <button type="button" on:click={toggleActivityDateFilter}
                      class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700">
                      Close
                    </button>
                  </div>
                </div>
              </div>
            {/if}
          </div>
        </div>

        {#if loadingActivities}
          <p class="text-gray-900 dark:text-white">Memuat aktivitas...</p>
        {:else if errorActivities}
          <p class="text-red-500">{errorActivities}</p>
        {:else if activities.length === 0}
          <div class="mt-4 bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
              <li class="px-4 py-4 sm:px-6">
                <p class="text-sm text-gray-500 dark:text-gray-300">Belum ada aktivitas untuk project ini.</p>
              </li>
            </ul>
          </div>
        {:else}
          {#if activityView === 'list'}
            <div class="mt-4 bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
              <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                {#each activities as activity (activity.id)}
                  <li>
                    <a
                      href={`/activities/${activity.id}`}
                      class="block hover:bg-gray-50 dark:hover:bg-neutral-950 px-4 py-4 sm:px-6 cursor-pointer"
                      on:click|preventDefault={() => openActivityDetailDrawer(activity)}
                      on:keydown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                          e.preventDefault();
                          openActivityDetailDrawer(activity);
                        }
                      }}
                      role="button"
                      aria-label={`Lihat detail aktivitas ${activity.name}`}
                    >
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
                            Jenis: {activity.jenis}
                            {#if activity.jenis === 'Vendor' && activity.mitra}
                              | Vendor: {activity.mitra.nama}
                            {:else if activity.jenis === 'Customer' && activity.mitra}
                              | Customer: {activity.mitra.nama}
                            {/if}
                            | From: {activity.from || '-'} | Deskripsi: {activity.short_desc}
                          </p>
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-300 sm:mt-0">
                          <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                          <p>Aktivitas: {new Date(activity.activity_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</p>
                        </div>
                      </div>
                    </a>
                    <div class="flex justify-end px-4 py-2 sm:px-6 space-x-2">
                      <button on:click|stopPropagation={() => openActivityDetailDrawer(activity)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-yellow-600 hover:bg-yellow-700">Detail</button>
                      {#if canUpdateActivity}
                        <button on:click|stopPropagation={() => openEditActivityModal(activity)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">Edit</button>
                      {/if}
                      {#if canDeleteActivity}
                        <button on:click|stopPropagation={() => handleDeleteActivity(activity.id)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800">Hapus</button>
                      {/if}
                    </div>
                  </li>
                {/each}
              </ul>
              {#if activities.length > 0}
                <Pagination
                  currentPage={activityCurrentPage}
                  lastPage={activityLastPage}
                  onPageChange={goToActivityPage}
                  totalItems={totalActivities}
                  itemsPerPage={activityPerPage}
                  perPageOptions={perPageOptions}
                  onPerPageChange={(n) => { activityPerPage = n; activityCurrentPage = 1; fetchActivities(); }}
                />
              {/if}
            </div>
          {/if}

          {#if activityView === 'table'}
            <div class="mt-4 bg-white dark:bg-black shadow-md rounded-lg">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                  <thead class="bg-gray-50 dark:bg-neutral-900">
                    <tr>
                      <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Tanggal Aktivitas</th>
                      <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Nama Aktivitas</th>
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
                          <a 
                            href={`/activities/${activity.id}`}
                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                            on:click|preventDefault={() => openActivityDetailDrawer(activity)}
                            on:keydown={(e) => {
                              if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                openActivityDetailDrawer(activity);
                              }
                            }}
                            role="button"
                            aria-label={`Lihat detail aktivitas ${activity.name}`}
                          >
                            {activity.name}
                          </a><br>
                          <span class="text-xs text-gray-500 dark:text-gray-400">From: {activity.from || '-'} | {activity.short_desc}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                          <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100">{activity.kategori}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{activity.jenis}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                          {#if activity.jenis === 'Vendor' && activity.mitra}{activity.mitra.nama}
                          {:else if activity.jenis === 'Customer' && activity.mitra}{activity.mitra.nama}
                          {:else}-{/if}
                        </td>
                        <td class="relative whitespace-nowrap px-3 py-4 text-left text-sm font-medium">
                          <div class="flex items-left space-x-2">
                            <button on:click={() => openActivityDetailDrawer(activity)} class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" title="Detail">
                              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                              <span class="sr-only">Detail, {activity.name}</span>
                            </button>
                            {#if canUpdateActivity}
                              <button on:click|stopPropagation={() => openEditActivityModal(activity)} title="Edit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span class="sr-only">Edit, {activity.name}</span>
                              </button>
                            {/if}
                            {#if canDeleteActivity}
                              <button on:click|stopPropagation={() => handleDeleteActivity(activity.id)} title="Delete" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
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
                <Pagination
                  currentPage={activityCurrentPage}
                  lastPage={activityLastPage}
                  onPageChange={goToActivityPage}
                  totalItems={totalActivities}
                  itemsPerPage={activityPerPage}
                  perPageOptions={perPageOptions}
                  onPerPageChange={(n) => { activityPerPage = n; activityCurrentPage = 1; fetchActivities(); }}
                />
              {/if}
            </div>
          {/if}
        {/if}
      </div>
    {/if}

    <!-- CERTIFICATES -->
    {#if activeTab === 'certificates'}
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row items-center justify-between mb-4 space-y-4 sm:space-y-0 sm:space-x-4">
          <div class="flex w-full sm:w-auto space-x-2">
            <select bind:value={certificateStatusFilter} on:change={handleCertificateFilterOrSearch}
              class="w-full sm:w-auto px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900 border border-gray-300
                     dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700">
              <option value="">Status: Semua</option>
              {#each certificateStatuses as s}<option value={s}>{s}</option>{/each}
            </select>
          </div>

          <div class="w-full sm:w-auto flex-grow">
            <div class="relative w-full sm:w-auto">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
              </div>
              <input
                type="text" placeholder="Cari sertifikat..." bind:value={certificateSearch} on:input={handleSearchDebounced}
                class="block w-full pl-10 pr-3 py-2 border rounded-md leading-5 bg-white text-gray-900 placeholder-gray-500 border-gray-300
                       focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm
                       dark:bg-neutral-900 dark:text-gray-100 dark:placeholder-gray-400 dark:border-gray-700"
              />
            </div>
          </div>

          <div class="flex space-x-2 w-full sm:w-auto">
            {#if canCreateCertificate}
              <button
                on:click={openCreateCertificateModal}
                class="px-4 py-2 w-full sm:w-auto border border-transparent text-sm font-medium rounded-md shadow-sm text-white
                       bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                       dark:focus:ring-offset-gray-800">
                Tambah Sertif
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
            on:keydown={handleCertificateViewKeydown}
          >
            <!-- TABLE -->
            <button
              on:click={() => (certificateView = 'table')}
              class="grid h-9 w-9 place-items-center rounded-md
                    text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-50
                    focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:focus-visible:ring-gray-500"
              class:bg-white={certificateView === 'table'}
              class:dark:bg-neutral-900={certificateView === 'table'}
              class:text-gray-900={certificateView === 'table'}
              class:dark:text-white={certificateView === 'table'}
              class:border={certificateView === 'table'}
              class:border-gray-300={certificateView === 'table'}
              class:dark:border-gray-700={certificateView === 'table'}
              role="tab"
              aria-selected={certificateView === 'table'}
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
              on:click={() => (certificateView = 'list')}
              class="grid h-9 w-9 place-items-center rounded-md
                    text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-50
                    focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:focus-visible:ring-gray-500"
              class:bg-white={certificateView === 'list'}
              class:dark:bg-neutral-900={certificateView === 'list'}
              class:text-gray-900={certificateView === 'list'}
              class:dark:text-white={certificateView === 'list'}
              class:border={certificateView === 'list'}
              class:border-gray-300={certificateView === 'list'}
              class:dark:border-gray-700={certificateView === 'list'}
              role="tab"
              aria-selected={certificateView === 'list'}
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

          <div class="relative certificate-date-filter-dropdown">
            <button
              on:click={toggleCertificateDateFilter}
              class="certificate-date-filter-button inline-flex items-center px-3 py-2 border rounded-md shadow-sm text-sm font-medium
                     text-gray-700 bg-white hover:bg-gray-50 border-gray-300
                     focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                     dark:text-gray-100 dark:bg-neutral-900 dark:border-gray-700 dark:hover:bg-neutral-800 dark:focus:ring-offset-gray-800"
              class:bg-indigo-50={certificateDateFromFilter || certificateDateToFilter}
              class:border-indigo-300={certificateDateFromFilter || certificateDateToFilter}
              class:text-indigo-700={certificateDateFromFilter || certificateDateToFilter}
            >
              <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              Filter Tanggal
              {#if certificateDateFromFilter || certificateDateToFilter}<div class="w-2 h-2 bg-indigo-600 rounded-full ml-2"></div>{/if}
              <svg class="w-4 h-4 transition-transform" class:rotate-180={showCertificateDateFilter} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            {#if showCertificateDateFilter}
              <div class="absolute right-0 mt-2 w-80 bg-white dark:bg-neutral-900 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                <div class="p-4">
                  <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Filter Tanggal Sertifikat</h3>
                    <button on:click={toggleCertificateDateFilter} aria-label="Tutup filter" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                  </div>
                  <div class="space-y-3">
                    <div class="space-y-1">
                      <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Urutkan Berdasarkan Create
                      </span>
                      <div class="inline-flex w-full rounded-md overflow-hidden border border-gray-300 dark:border-gray-700" role="tablist" aria-label="Urutan tanggal sertifikat">
                        <select
                          bind:value={certificateSortDir}
                          on:change={() => { certificateSortBy = 'created'; certificateCurrentPage = 1; fetchCertificates(); }}
                          class="w-full px-3 py-2 rounded-md text-sm font-semibold bg-white text-gray-900
                                dark:bg-neutral-900 dark:text-gray-100"
                          title="Urutkan berdasarkan waktu dibuat"
                        >
                          <option value="desc">Terbaru</option>
                          <option value="asc">Terlama</option>
                        </select>
                      </div>
                      <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Urutkan Tanggal
                      </span>

                      <!-- Pilih field tanggal yang diurutkan -->
                      <select
                        bind:value={certificateDateSortField}
                        class="w-full mb-2 px-3 py-2 rounded-md text-sm border border-gray-300 dark:border-gray-700
                              bg-white text-gray-900 dark:bg-neutral-900 dark:text-gray-100"
                        title="Pilih tanggal yang diurutkan"
                      >
                        <option value="date_of_issue">Tanggal Terbit</option>
                        <option value="date_of_expired">Tanggal Expired</option>
                      </select>

                      <!-- Segmented: Terbaru/Terlama -->
                      <div class="inline-flex w-full rounded-md overflow-hidden border border-gray-300 dark:border-gray-700" role="tablist" aria-label="Urutan tanggal sertifikat">
                        <button
                          type="button"
                          on:click={() => { certificateSortBy = certificateDateSortField; certificateSortDir = 'desc'; certificateCurrentPage = 1; fetchCertificates(); }}
                          class="w-full px-3 py-1.5 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                          class:bg-indigo-600={certificateSortBy===certificateDateSortField && certificateSortDir==='desc'}
                          class:text-white={certificateSortBy===certificateDateSortField && certificateSortDir==='desc'}
                          class:bg-white={!(certificateSortBy===certificateDateSortField && certificateSortDir==='desc')}
                          class:text-gray-900={!(certificateSortBy===certificateDateSortField && certificateSortDir==='desc')}
                          class:dark:bg-neutral-900={!(certificateSortBy===certificateDateSortField && certificateSortDir==='desc')}
                          class:dark:text-gray-100={!(certificateSortBy===certificateDateSortField && certificateSortDir==='desc')}
                          aria-selected={certificateSortBy===certificateDateSortField && certificateSortDir==='desc'}
                          role="tab"
                        >
                          Terbaru dulu
                        </button>
                        <button
                          type="button"
                          on:click={() => { certificateSortBy = certificateDateSortField; certificateSortDir = 'asc'; certificateCurrentPage = 1; fetchCertificates(); }}
                          class="w-full px-3 py-1.5 text-sm font-semibold transition-colors border-l border-gray-300 dark:border-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                          class:bg-indigo-600={certificateSortBy===certificateDateSortField && certificateSortDir==='asc'}
                          class:text-white={certificateSortBy===certificateDateSortField && certificateSortDir==='asc'}
                          class:bg-white={!(certificateSortBy===certificateDateSortField && certificateSortDir==='asc')}
                          class:text-gray-900={!(certificateSortBy===certificateDateSortField && certificateSortDir==='asc')}
                          class:dark:bg-neutral-900={!(certificateSortBy===certificateDateSortField && certificateSortDir==='asc')}
                          class:dark:text-gray-100={!(certificateSortBy===certificateDateSortField && certificateSortDir==='asc')}
                          aria-selected={certificateSortBy===certificateDateSortField && certificateSortDir==='asc'}
                          role="tab"
                        >
                          Terlama dulu
                        </button>
                      </div>

                      <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Gunakan menu <b>Sortir</b> di toolbar untuk kembali ke urutan <b>Create</b>.
                      </p>
                    </div>

                    <div>
                      <label for="cert_filter_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal Terbit</label>
                      <input id="cert_filter_from" type="date" bind:value={certificateDateFromFilter} on:change={handleCertificateDateFilter}
                        class="w-full px-3 py-2 border rounded-md text-sm border-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500
                               dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700" />
                    </div>
                    <div>
                      <label for="cert_filter_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal Terbit</label>
                      <input id="cert_filter_to" type="date" bind:value={certificateDateToFilter} on:change={handleCertificateDateFilter}
                        class="w-full px-3 py-2 border rounded-md text-sm border-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500
                               dark:bg-neutral-900 dark:text-gray-100 dark:border-gray-700" />
                    </div>

                    {#if certificateDateFromFilter || certificateDateToFilter}
                      <div class="text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-neutral-800 p-2 rounded">
                        <strong>Filter Aktif:</strong><br />
                        {#if certificateDateFromFilter && certificateDateToFilter}
                          {new Date(certificateDateFromFilter).toLocaleDateString('id-ID')} - {new Date(certificateDateToFilter).toLocaleDateString('id-ID')}
                        {:else if certificateDateFromFilter}
                          Dari {new Date(certificateDateFromFilter).toLocaleDateString('id-ID')}
                        {:else if certificateDateToFilter}
                          Sampai {new Date(certificateDateToFilter).toLocaleDateString('id-ID')}
                        {/if}
                      </div>
                    {/if}
                  </div>

                  <div class="flex justify-between mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button
                      type="button"
                      on:click={() => {
                        certificateDateFromFilter = '';
                        certificateDateToFilter = '';
                        certificateSortBy = 'created';
                        certificateSortDir = 'desc';
                        certificateCurrentPage = 1;
                        fetchCertificates();
                      }}
                      class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200
                            dark:text-gray-200 dark:bg-neutral-800 dark:border-gray-700 dark:hover:bg-neutral-700"
                    >
                      Clear All
                    </button>
                    <button 
                      type="button" 
                      on:click={toggleCertificateDateFilter}
                      class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700"
                    >
                      Close
                    </button>
                  </div>
                </div>
              </div>
            {/if}
          </div>
        </div>

        {#if loadingCertificates}
          <p class="text-gray-900 dark:text-white">Memuat sertifikat...</p>
        {:else if errorCertificates}
          <p class="text-red-500">{errorCertificates}</p>
        {:else if certificates.length === 0}
          <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
              <li class="px-4 py-4 sm:px-6">
                <p class="text-sm text-gray-500 dark:text-gray-300">Belum ada sertifikat untuk proyek ini.</p>
              </li>
            </ul>
          </div>
        {:else}
          {#if certificateView === 'list'}
            <div class="bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
              <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                {#each certificates as item (item.id)}
                  <li>
                    <a
                      href={`/certificates/${item.id}`}
                      class="block hover:bg-gray-50 dark:hover:bg-neutral-950 px-4 py-4 sm:px-6 cursor-pointer"
                      on:click|preventDefault={() => openCertificateDetailDrawer(item)}
                      on:keydown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                          e.preventDefault();
                          openCertificateDetailDrawer(item);
                        }
                      }}
                      role="button"
                      aria-label={`Lihat detail sertifikat ${item.name}`}
                    >
                      <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">{item.name}</p>
                        <div class="ml-2 flex-shrink-0 flex">
                          <span class={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${getCertificateStatusBadgeClasses(item.status)}`}>{item.status}</span>
                        </div>
                      </div>
                      <div class="mt-2 sm:flex sm:justify-between">
                        <div class="sm:flex">
                          <p class="flex items-center text-sm text-gray-500 dark:text-gray-300">Barang: {item.barang_certificate?.name || '-'} | No: {item.no_certificate}</p>
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-300 sm:mt-0">
                          <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                          {#if item.date_of_issue}
                            <p>Terbit: {new Date(item.date_of_issue).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</p>
                          {:else}<p>Terbit: -</p>{/if}
                        </div>
                      </div>
                    </a>
                    <div class="flex justify-end px-4 py-2 sm:px-6 space-x-2">
                      <button on:click|stopPropagation={() => openCertificateDetailDrawer(item)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-yellow-600 hover:bg-yellow-700">Detail</button>
                      <button on:click|stopPropagation={() => openEditCertificateModal(item)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700">Edit</button>
                      <button on:click|stopPropagation={() => handleDeleteCertificate(item.id)} class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-red-600 hover:bg-red-700">Hapus</button>
                    </div>
                  </li>
                {/each}
              </ul>
              {#if certificates.length > 0}
                <Pagination
                  currentPage={certificateCurrentPage}
                  lastPage={certificateLastPage}
                  onPageChange={goToCertificatePage}
                  totalItems={totalCertificates}
                  itemsPerPage={certificatePerPage}
                  perPageOptions={perPageOptions}
                  onPerPageChange={(n) => { certificatePerPage = n; certificateCurrentPage = 1; fetchCertificates(); }}
                />
              {/if}
            </div>
          {/if}

          {#if certificateView === 'table'}
            <div class="mt-4 bg-white dark:bg-black shadow-md rounded-lg">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                  <thead class="bg-gray-50 dark:bg-neutral-900">
                    <tr>
                      <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Nama Sertifikat</th>
                      <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">No. Sertifikat</th>
                      <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Barang Sertifikat</th>
                      <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th>
                      <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Terbit</th>
                      <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Expired</th>
                      <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-black">
                    {#each certificates as item (item.id)}
                      <tr>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                          <a 
                            href={`/certificates/${item.id}`}
                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                            on:click|preventDefault={() => openCertificateDetailDrawer(item)}
                            on:keydown={(e) => {
                              if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                openCertificateDetailDrawer(item);
                              }
                            }}
                            role="button"
                            aria-label={`Lihat detail sertifikat ${item.name}`}
                          >
                            {item.name}
                          </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{item.no_certificate}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{item.barang_certificate?.name || "-"}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                          <span class={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${getCertificateStatusBadgeClasses(item.status)}`}>{item.status}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                          {#if item.date_of_issue}{new Date(item.date_of_issue).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}{:else}<span class="text-gray-500 dark:text-gray-400">-</span>{/if}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                          {#if item.date_of_expired}{new Date(item.date_of_expired).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}{:else}<span class="text-gray-500 dark:text-gray-400">-</span>{/if}
                        </td>
                        <td class="relative whitespace-nowrap px-3 py-4 text-sm">
                          <div class="flex items-center space-x-2">
                            <button title="Detail" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" on:click={() => openCertificateDetailDrawer(item)}>
                              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"/></svg>
                              <span class="sr-only">Detail, {item.name}</span>
                            </button>
                            {#if canUpdateCertificate}
                              <button title="Edit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" on:click={() => openEditCertificateModal(item)}>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span class="sr-only">Edit, {item.name}</span>
                              </button>
                            {/if}
                            {#if canDeleteCertificate}
                              <button title="Hapus" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" on:click={() => handleDeleteCertificate(item.id)}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
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
              {#if certificates.length > 0}
                <Pagination
                  currentPage={certificateCurrentPage}
                  lastPage={certificateLastPage}
                  onPageChange={goToCertificatePage}
                  totalItems={totalCertificates}
                  itemsPerPage={certificatePerPage}
                  perPageOptions={perPageOptions}
                  onPerPageChange={(n) => { certificatePerPage = n; certificateCurrentPage = 1; fetchCertificates(); }}
                />
              {/if}
            </div>
          {/if}
        {/if}
      </div>
    {/if}
  </div>

    {#if showEditProjectModal}
      <ProjectFormModal bind:show={showEditProjectModal} title="Edit Project" submitLabel="Update Project" idPrefix="edit_project"
        form={editProjectForm} {customers} {projectStatuses} {projectKategoris} onSubmit={handleSubmitUpdateProject} />
    {/if}

    <ActivityFormModal
      bind:show={showCreateActivityModal}
      title="Form Tambah Aktivitas"
      submitLabel="Tambah Aktivitas"
      idPrefix="create_activity"
      form={createActivityForm}
      projects={project ? [project] : []}
      showProjectSelect={false}
      {vendors}
      {activityKategoriList}
      {activityJenisList}
      allowRemoveAttachment={false}
      onSubmit={handleSubmitCreateActivity}
    />

    {#if editingActivity}
      <ActivityFormModal
        bind:show={showEditActivityModal}
        title="Edit Aktivitas"
        submitLabel="Update Aktivitas"
        idPrefix="edit_activity"
        form={editActivityForm}
        projects={project ? [project] : []}
        showProjectSelect={false}
        {vendors}
        {activityKategoriList}
        {activityJenisList}
        allowRemoveAttachment={true}
        onSubmit={handleSubmitUpdateActivity}
      />
    {/if}

    <Drawer bind:show={showActivityDetailDrawer} title="Detail Activity" on:close={() => (showActivityDetailDrawer = false)}>
      <ActivityDetail activity={selectedActivity} />
    </Drawer>

    <CertificateFormModal
      bind:show={showCreateCertificateModal}
      title="Tambah Sertifikat"
      submitLabel="Simpan"
      idPrefix="create_cert"
      form={certificateForm}
      projects={[]}
      barangOptions={certificateBarangOptions}
      statuses={certificateStatuses}
      allowRemoveAttachment={true}
      showProjectSelect={false}
      onSubmit={handleSubmitCreateCertificate}
    />

    {#if editingCertificate}
      <CertificateFormModal
        bind:show={showEditCertificateModal}
        title="Edit Sertifikat"
        submitLabel="Update"
        idPrefix="edit_cert"
        form={certificateForm}
        projects={[]}
        barangOptions={certificateBarangOptions}
        statuses={certificateStatuses}
        allowRemoveAttachment={true}
        showProjectSelect={false}
        onSubmit={handleSubmitUpdateCertificate}
      />
    {/if}

    <Drawer bind:show={showCertificateDetailDrawer} title="Detail Sertifikat" on:close={() => (showCertificateDetailDrawer = false)}>
      <CertificatesDetail certificates={selectedCertificate} />
    </Drawer>
{/if}
