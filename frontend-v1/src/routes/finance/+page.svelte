<script lang="ts">
  import { onMount } from 'svelte';
  import axiosClient from '$lib/axiosClient';
  import Drawer from '$lib/components/Drawer.svelte';
  import FinanceDetail from '$lib/components/detail/FinanceDetail.svelte';

  let loading = false;
  let error = '';
  type ReportMode = 'month' | 'project';

  let reportData: any[] = [];
  let meta: Record<string, any> = { total_records: 0, total_value: 0, period: '', project: null, filters: {} };
  let showDetailDrawer = false;
  let selectedFinanceItem: any = null;
  let reportMode: ReportMode = 'month';
  let projects: Array<{ id: number; name: string }> = [];
  let selectedProjectId: number | string = '';
  let projectStartDate = '';
  let projectEndDate = '';

  // Default ke bulan & tahun saat ini
  let selectedMonth = new Date().getMonth() + 1;
  let selectedYear = new Date().getFullYear();

  const months = [
    { val: 1, label: 'Januari' }, { val: 2, label: 'Februari' }, { val: 3, label: 'Maret' },
    { val: 4, label: 'April' }, { val: 5, label: 'Mei' }, { val: 6, label: 'Juni' },
    { val: 7, label: 'Juli' }, { val: 8, label: 'Agustus' }, { val: 9, label: 'September' },
    { val: 10, label: 'Oktober' }, { val: 11, label: 'November' }, { val: 12, label: 'Desember' }
  ];

  // GENERATE TAHUN: Mulai dari 2020 sampai tahun depan (agar tidak hardcoded 2026)
  const startYear = 2020;
  const currentYear = new Date().getFullYear();
  const endYear = currentYear + 1;
  // Membuat array range tahun
  const years = Array.from({ length: (endYear - startYear) + 1 }, (_, i) => startYear + i);

  async function fetchReport() {
    loading = true;
    error = '';
    try {
      let endpoint = '/finance';
      let params: Record<string, any> = { 
        type: reportMode,
        month: selectedMonth, 
        year: selectedYear 
      };

      if (reportMode === 'project') {
        const normalizedProjectId =
          typeof selectedProjectId === 'number'
            ? selectedProjectId
            : Number(selectedProjectId || 0);

        if (!normalizedProjectId) {
          error = 'Silakan pilih project terlebih dahulu.';
          loading = false;
          return;
        }

        if (projectStartDate && projectEndDate) {
          const start = new Date(projectStartDate);
          const end = new Date(projectEndDate);
          if (start > end) {
            error = 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.';
            loading = false;
            return;
          }
        }

        params = {
          type: 'project',
          project_id: normalizedProjectId,
          start_date: projectStartDate || undefined,
          end_date: projectEndDate || undefined
        };
      }

      const res = await axiosClient.get(endpoint, { params });
      reportData = res.data.data;
      meta = res.data.meta;
    } catch (err: any) {
      console.error(err);
      error = err.response?.data?.message || 'Gagal mengambil laporan keuangan';
    } finally {
      loading = false;
    }
  }

  async function fetchProjects() {
    try {
      const res = await axiosClient.get('/projects', { params: { per_page: 500 } });
      const payload = Array.isArray(res.data?.data) ? res.data.data : Array.isArray(res.data) ? res.data : [];
      projects = payload.map((project: any) => ({ id: project.id, name: project.name }));
    } catch (err) {
      console.error('Gagal memuat daftar project:', err);
    }
  }

  function handleModeChange(mode: ReportMode) {
    if (reportMode === mode) return;
    reportMode = mode;
    error = '';
    reportData = [];
    meta = { total_records: 0, total_value: 0, period: '', project: null, filters: {} };

    if (mode === 'month') {
      selectedProjectId = '';
      projectStartDate = '';
      projectEndDate = '';
      fetchReport();
    }
  }

  function formatRupiah(val: number) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(val);
  }

  $: reportSubtitle = reportMode === 'month'
    ? 'Rekapitulasi aktivitas keuangan per bulan'
    : 'Rekapitulasi aktivitas keuangan per project';

  onMount(() => {
    fetchReport();
    fetchProjects();
  });

  function openFinanceDetailDrawer(item: any) {
    selectedFinanceItem = item;
    showDetailDrawer = true;
  }

  function closeFinanceDetailDrawer() {
    showDetailDrawer = false;
  }

  function handleFinanceValueSaved(event: CustomEvent) {
    const detail = event.detail ?? {};
    const activityId = detail.activityId;
    if (!activityId) return;

    reportData = reportData.map((row) => {
      if (row?.id === activityId) {
        const nextValue = Number(detail.value ?? row.value ?? 0);
        return {
          ...row,
          ...detail.item, // Update with data from new FinanceResource
          value: nextValue,
          value_formatted: detail.value_formatted ?? formatRupiah(nextValue)
        };
      }
      return row;
    });

    meta = {
      ...meta,
      total_value: reportData.reduce((sum, row) => sum + Number(row.value ?? 0), 0)
    };
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
  $: lockBodyScroll(showDetailDrawer);
</script>

<div class="flex flex-col gap-6">
  <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-gray-800 dark:text-white">Dokumen Keuangan</h1>
      <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{reportSubtitle}</p>
      
      <div class="mt-4 inline-flex p-1 rounded-lg bg-gray-100 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
        <label class="relative flex cursor-pointer items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-all {reportMode === 'month' ? 'bg-white text-emerald-600 shadow-sm dark:bg-emerald-600 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'}">
          <input
            type="radio"
            name="report-mode"
            class="sr-only"
            checked={reportMode === 'month'}
            on:change={() => handleModeChange('month')}
          />
          <span>Per Bulan</span>
        </label>
        <label class="relative flex cursor-pointer items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-all {reportMode === 'project' ? 'bg-white text-emerald-600 shadow-sm dark:bg-emerald-600 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'}">
          <input
            type="radio"
            name="report-mode"
            class="sr-only"
            checked={reportMode === 'project'}
            on:change={() => handleModeChange('project')}
          />
          <span>Per Project</span>
        </label>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-2 p-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black shadow-sm">
      {#if reportMode === 'month'}
        <select 
          bind:value={selectedMonth} 
          on:change={fetchReport} 
          class="h-10 w-full sm:w-40 px-2 rounded-lg border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-neutral-900 text-gray-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"
        >
          {#each months as m (m.val)}
            <option value={m.val}>{m.label}</option>
          {/each}
        </select>

        <select 
          bind:value={selectedYear} 
          on:change={fetchReport} 
          class="h-10 w-full sm:w-28 px-2 rounded-lg border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-neutral-900 text-gray-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"
        >
          {#each years as y (y)}
            <option value={y}>{y}</option>
          {/each}
        </select>

      {:else}
        <select 
          bind:value={selectedProjectId} 
          on:change={fetchReport} 
          class="h-10 w-full sm:w-48 px-2 rounded-lg border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-neutral-900 text-gray-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"
        >
          <option value="">Pilih Project</option>
          {#each projects as project (project.id)}
            <option value={project.id}>{project.name}</option>
          {/each}
        </select>
        <input 
          type="date" 
          bind:value={projectStartDate} 
          on:change={fetchReport} 
          class="h-10 w-full sm:w-auto px-2 rounded-lg border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-neutral-900 text-gray-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500" 
        />
        
        <span class="hidden sm:flex items-center text-gray-400">-</span>
        <input 
          type="date" 
          bind:value={projectEndDate} 
          on:change={fetchReport} 
          class="h-10 w-full sm:w-auto px-2 rounded-lg border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-neutral-900 text-gray-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500" 
        />
      {/if}
      <button 
        on:click={fetchReport} 
        class="h-10 px-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm whitespace-nowrap"
      >
        Refresh
      </button>
    </div>
  </div>

  <!-- <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white dark:bg-black p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 md:col-span-1">
      <p class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wide">
        {reportMode === 'month' ? 'Total Nilai (Periode Terpilih)' : 'Total Nilai (Project Terpilih)'}
      </p>
      <h2 class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">
        {formatRupiah(meta.total_value || 0)}
      </h2>
      <p class="text-xs text-gray-400 mt-1">
        {reportMode === 'month' && meta.period
          ? `Periode ${meta.period}`
          : reportMode === 'project' && meta?.project?.name
            ? `Project ${meta.project.name}`
            : ''}
      </p>
      <p class="text-xs text-gray-400 mt-1">Total {meta.total_records} transaksi</p>
    </div>
  </div> -->

  <div class="bg-white dark:bg-black shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left divide-y divide-gray-300 dark:divide-gray-700 border-collapse">
        <thead>
          <tr class="bg-gray-50 dark:bg-neutral-900 text-xs uppercase text-gray-500 dark:text-gray-300 font-semibold tracking-wider">
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">No</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Tanggal</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Kategori</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Activity</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Project</th>
            <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">Nilai (IDR)</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-200">
          {#if loading}
            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Memuat data...</td></tr>
          {:else if error}
            <tr><td colspan="6" class="px-6 py-8 text-center text-red-600 dark:text-red-400">{error}</td></tr>
          {:else if reportData.length === 0}
            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 italic">Tidak ada data keuangan pada periode ini.</td></tr>
          {:else}
            {#each reportData as item, i (item.id)}
              <tr class="hover:bg-gray-50 dark:hover:bg-neutral-950 transition-colors">
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {i + 1}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {item.activity_date}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    {item.kategori}
                  </span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  <button
                    type="button"
                    class="text-left font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                    on:click={() => openFinanceDetailDrawer(item)}
                    on:keydown={(event) => {
                      if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openFinanceDetailDrawer(item);
                      }
                    }}
                  >
                    {item.name}
                  </button>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                  {item.project?.name || '-'}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-right font-bold text-gray-800 dark:text-white font-mono">
                  {item.value_formatted}
                </td>
              </tr>
              {/each}
          {/if}
        </tbody>
      </table>
    </div>
  </div>
</div>

<Drawer bind:show={showDetailDrawer} title="Detail Dokumen Keuangan" on:close={closeFinanceDetailDrawer}>
  <FinanceDetail item={selectedFinanceItem} on:saved={handleFinanceValueSaved} />
</Drawer>