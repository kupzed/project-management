<script lang="ts">
  import { onMount } from 'svelte';
  import Drawer from '$lib/components/Drawer.svelte';
  import FinanceDetail from '$lib/components/detail/FinanceDetail.svelte';
  import {
    fetchFinanceProjects,
    fetchFinanceReport,
    type FinanceItem,
    type FinanceProjectOption,
    type FinanceReportMeta,
    type FinanceReportMode,
    type FinanceReportParams,
    type FinanceSavedPayload
  } from '$lib/services/financeService';
  import { extractApiErrors } from '$lib/utils/errors';
  import { formatCurrency } from '$lib/utils/formatters';
  import { lockBodyScroll } from '$lib/utils/scroll-lock';

  const emptyMeta: FinanceReportMeta = {
    total_records: 0,
    total_value: 0,
    period: '',
    project: null,
    filters: {}
  };

  let loading = $state(false);
  let error = $state('');
  let reportData = $state<FinanceItem[]>([]);
  let meta = $state<FinanceReportMeta>({ ...emptyMeta });
  let showDetailDrawer = $state(false);
  let selectedFinanceItem = $state<FinanceItem | null>(null);
  let reportMode = $state<FinanceReportMode>('month');
  let projects = $state<FinanceProjectOption[]>([]);
  let selectedProjectId = $state<number | string>('');
  let projectStartDate = $state('');
  let projectEndDate = $state('');

  // Default ke bulan & tahun saat ini
  let selectedMonth = $state(new Date().getMonth() + 1);
  let selectedYear = $state(new Date().getFullYear());

  const months = [
    { val: 1, label: 'Januari' },
    { val: 2, label: 'Februari' },
    { val: 3, label: 'Maret' },
    { val: 4, label: 'April' },
    { val: 5, label: 'Mei' },
    { val: 6, label: 'Juni' },
    { val: 7, label: 'Juli' },
    { val: 8, label: 'Agustus' },
    { val: 9, label: 'September' },
    { val: 10, label: 'Oktober' },
    { val: 11, label: 'November' },
    { val: 12, label: 'Desember' }
  ];

  // GENERATE TAHUN: Mulai dari 2020 sampai tahun depan (agar tidak hardcoded 2026)
  const startYear = 2020;
  const currentYear = new Date().getFullYear();
  const endYear = currentYear + 1;
  // Membuat array range tahun
  const years = Array.from({ length: endYear - startYear + 1 }, (_, i) => startYear + i);

  let reportSubtitle = $derived(
    reportMode === 'month'
      ? 'Rekapitulasi aktivitas keuangan per bulan'
      : 'Rekapitulasi aktivitas keuangan per project'
  );

  async function fetchReport() {
    loading = true;
    error = '';
    try {
      let params: FinanceReportParams = {
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

      const result = await fetchFinanceReport(params);
      reportData = result.data;
      meta = result.meta;
    } catch (err) {
      console.error(err);
      error = extractApiErrors(err);
    } finally {
      loading = false;
    }
  }

  async function fetchProjects() {
    try {
      projects = await fetchFinanceProjects();
    } catch (err) {
      console.error('Gagal memuat daftar project:', err);
    }
  }

  function handleModeChange(mode: FinanceReportMode) {
    if (reportMode === mode) return;
    reportMode = mode;
    error = '';
    reportData = [];
    meta = { ...emptyMeta };

    if (mode === 'month') {
      selectedProjectId = '';
      projectStartDate = '';
      projectEndDate = '';
      void fetchReport();
    }
  }

  function formatRupiah(val: number) {
    return formatCurrency(val);
  }

  onMount(() => {
    void fetchReport();
    void fetchProjects();
  });

  function openFinanceDetailDrawer(item: FinanceItem) {
    selectedFinanceItem = item;
    showDetailDrawer = true;
  }

  function closeFinanceDetailDrawer() {
    showDetailDrawer = false;
  }

  function handleFinanceValueSaved(detail: FinanceSavedPayload) {
    const { activityId } = detail;
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

  function handleActivityKeydown(event: KeyboardEvent, item: FinanceItem) {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      openFinanceDetailDrawer(item);
    }
  }

  $effect(() => {
    lockBodyScroll(showDetailDrawer);
  });
</script>

<div class="flex flex-col gap-6">
  <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-gray-800 dark:text-white">
        Dokumen Keuangan
      </h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{reportSubtitle}</p>

      <div
        class="mt-4 inline-flex rounded-lg border border-gray-200 bg-gray-100 p-1 dark:border-gray-700 dark:bg-gray-800/50"
      >
        <label
          class="relative flex cursor-pointer items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-all {reportMode ===
          'month'
            ? 'bg-white text-emerald-600 shadow-sm dark:bg-emerald-600 dark:text-white'
            : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'}"
        >
          <input
            type="radio"
            name="report-mode"
            class="sr-only"
            checked={reportMode === 'month'}
            onchange={() => handleModeChange('month')}
          />
          <span>Per Bulan</span>
        </label>
        <label
          class="relative flex cursor-pointer items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-all {reportMode ===
          'project'
            ? 'bg-white text-emerald-600 shadow-sm dark:bg-emerald-600 dark:text-white'
            : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'}"
        >
          <input
            type="radio"
            name="report-mode"
            class="sr-only"
            checked={reportMode === 'project'}
            onchange={() => handleModeChange('project')}
          />
          <span>Per Project</span>
        </label>
      </div>
    </div>

    <div
      class="flex flex-col gap-2 rounded-xl border border-gray-200 bg-white p-2 shadow-sm sm:flex-row dark:border-gray-700 dark:bg-black"
    >
      {#if reportMode === 'month'}
        <select
          bind:value={selectedMonth}
          onchange={() => void fetchReport()}
          class="h-10 w-full rounded-lg border-gray-300 bg-gray-50 px-2 text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 sm:w-40 dark:border-gray-600 dark:bg-neutral-900 dark:text-white"
        >
          {#each months as m (m.val)}
            <option value={m.val}>{m.label}</option>
          {/each}
        </select>

        <select
          bind:value={selectedYear}
          onchange={() => void fetchReport()}
          class="h-10 w-full rounded-lg border-gray-300 bg-gray-50 px-2 text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 sm:w-28 dark:border-gray-600 dark:bg-neutral-900 dark:text-white"
        >
          {#each years as y (y)}
            <option value={y}>{y}</option>
          {/each}
        </select>
      {:else}
        <select
          bind:value={selectedProjectId}
          onchange={() => void fetchReport()}
          class="h-10 w-full rounded-lg border-gray-300 bg-gray-50 px-2 text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 sm:w-48 dark:border-gray-600 dark:bg-neutral-900 dark:text-white"
        >
          <option value="">Pilih Project</option>
          {#each projects as project (project.id)}
            <option value={project.id}>{project.name}</option>
          {/each}
        </select>
        <input
          type="date"
          bind:value={projectStartDate}
          onchange={() => void fetchReport()}
          class="h-10 w-full rounded-lg border-gray-300 bg-gray-50 px-2 text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 sm:w-auto dark:border-gray-600 dark:bg-neutral-900 dark:text-white"
        />

        <span class="hidden items-center text-gray-400 sm:flex">-</span>
        <input
          type="date"
          bind:value={projectEndDate}
          onchange={() => void fetchReport()}
          class="h-10 w-full rounded-lg border-gray-300 bg-gray-50 px-2 text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 sm:w-auto dark:border-gray-600 dark:bg-neutral-900 dark:text-white"
        />
      {/if}
      <button
        onclick={() => void fetchReport()}
        class="h-10 rounded-lg bg-emerald-600 px-5 text-sm font-medium whitespace-nowrap text-white shadow-sm transition-colors hover:bg-emerald-700"
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

  <div class="overflow-hidden bg-white shadow-sm dark:bg-black">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse divide-y divide-gray-300 text-left dark:divide-gray-700">
        <thead>
          <tr
            class="bg-gray-50 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:bg-neutral-900 dark:text-gray-300"
          >
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >No</th
            >
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Tanggal</th
            >
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Kategori</th
            >
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Activity</th
            >
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Project</th
            >
            <th
              class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-100"
              >Nilai (IDR)</th
            >
          </tr>
        </thead>
        <tbody
          class="divide-y divide-gray-100 text-sm text-gray-700 dark:divide-gray-700 dark:text-gray-200"
        >
          {#if loading}
            <tr
              ><td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400"
                >Memuat data...</td
              ></tr
            >
          {:else if error}
            <tr
              ><td colspan="6" class="px-6 py-8 text-center text-red-600 dark:text-red-400"
                >{error}</td
              ></tr
            >
          {:else if reportData.length === 0}
            <tr
              ><td colspan="6" class="px-6 py-8 text-center text-gray-500 italic dark:text-gray-400"
                >Tidak ada data keuangan pada periode ini.</td
              ></tr
            >
          {:else}
            {#each reportData as item, i (item.id)}
              <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-neutral-950">
                <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
                  {i + 1}
                </td>
                <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
                  {item.activity_date}
                </td>
                <td class="px-3 py-4 text-sm whitespace-nowrap">
                  <span
                    class="inline-flex items-center rounded-full bg-gray-300 px-2.5 py-0.5 text-xs font-medium text-gray-900 dark:bg-gray-700 dark:text-gray-100"
                  >
                    {item.kategori}
                  </span>
                </td>
                <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
                  <button
                    type="button"
                    class="text-left font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                    onclick={() => openFinanceDetailDrawer(item)}
                    onkeydown={(event) => handleActivityKeydown(event, item)}
                  >
                    {item.name}
                  </button>
                </td>
                <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
                  {item.project?.name || '-'}
                </td>
                <td
                  class="px-3 py-4 text-right font-mono font-bold whitespace-nowrap text-gray-800 dark:text-white"
                >
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

<Drawer
  bind:show={showDetailDrawer}
  title="Detail Dokumen Keuangan"
  onClose={closeFinanceDetailDrawer}
>
  <FinanceDetail item={selectedFinanceItem} onSaved={handleFinanceValueSaved} />
</Drawer>
