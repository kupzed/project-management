<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import axiosClient from '$lib/axiosClient';
  import Chart from 'chart.js/auto';
  import type { Chart as ChartJS, ChartOptions } from 'chart.js';

  // ==== STATE ====
  let loading = true;
  let error = '';

  // cards
  let totals = {
    total_projects: 0,
    ongoing: 0,
    prospect: 0,
    complete: 0,
    cancel: 0,
    cert_projects: 0,
    cert_active: 0,
    cert_expiring_30: 0
  };

  // list
  let latestProjects: any[] = [];

  // Warna sesuai status (pakai palet Tailwind)
  const STATUS_COLOR: Record<string, string> = {
    Ongoing:  '#3B82F6',
    Prospect: '#F59E0B',
    Complete: '#10B981',
    Cancel:   '#EF4444',
  };

  // charts data
  let trend = { labels: [] as string[], counts: [] as number[] };
  let statusDist = { labels: [] as string[], counts: [] as number[] };
  let kategoriDist = { labels: [] as string[], counts: [] as number[] };
  let topCustomers = { labels: [] as string[], counts: [] as number[] };

  // canvas refs
  let cTrend: HTMLCanvasElement;
  let cStatus: HTMLCanvasElement;
  let cKategori: HTMLCanvasElement;
  let cTopCust: HTMLCanvasElement;

  // chart instances
  let chartTrend: ChartJS | null = null;
  let chartStatus: ChartJS | null = null;
  let chartKategori: ChartJS | null = null;
  let chartTopCust: ChartJS | null = null;

  // ==== HELPERS ====
  function destroyCharts() {
    chartTrend?.destroy();
    chartStatus?.destroy();
    chartKategori?.destroy();
    chartTopCust?.destroy();
    chartTrend = chartStatus = chartKategori = chartTopCust = null;
  }

  function drawCharts() {
    destroyCharts();

    const ticksInt = { beginAtZero: true, ticks: { precision: 0 as number, stepSize: 1 } };

    // Tren 12 bulan (Line)
    chartTrend = new Chart(cTrend, {
      type: 'line',
      data: {
        labels: trend.labels,
        datasets: [
          {
            label: 'Project',
            data: trend.counts,
            tension: 0.35,
            fill: false
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: ticksInt },
        plugins: { legend: { display: false } }
      } as ChartOptions<'line'>
    });

    // Distribusi status (Doughnut)
    chartStatus = new Chart(cStatus, {
      type: 'doughnut',
      data: {
        labels: statusDist.labels,
        datasets: [{
          data: statusDist.counts,
          backgroundColor: statusDist.labels.map(l => STATUS_COLOR[l] ?? '#9CA3AF'),
          borderColor: '#FFFFFF',
          borderWidth: 2,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '55%',
        plugins: { legend: { position: 'bottom' } }
      }
    });

    // Project per Kategori (Bar)
    chartKategori = new Chart(cKategori, {
      type: 'bar',
      data: {
        labels: kategoriDist.labels,
        datasets: [{ label: 'Jumlah', data: kategoriDist.counts }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: ticksInt },
        plugins: { legend: { display: false } }
      } as ChartOptions<'bar'>
    });

    // Top Customer by Project (Horizontal Bar)
    chartTopCust = new Chart(cTopCust, {
      type: 'bar',
      data: {
        labels: topCustomers.labels,
        datasets: [{ label: 'Project', data: topCustomers.counts }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: { x: ticksInt },
        plugins: { legend: { display: false } }
      } as ChartOptions<'bar'>
    });
  }

  async function fetchDashboard() {
    loading = true;
    error = '';
    try {
      const res = await axiosClient.get('/dashboard');
      const d = res.data?.data ?? {};

      latestProjects = d.latest_projects ?? [];

      totals = {
        ...totals,
        ...(d.totals ?? {})
      };

      trend = d.trend_12_months ?? trend;
      statusDist = d.status_distribution ?? statusDist;
      kategoriDist = d.kategori_distribution ?? kategoriDist;
      topCustomers = d.top_customers ?? topCustomers;

      // render chart setelah data ada
      setTimeout(drawCharts, 0);
    } catch (e: any) {
      error = e?.response?.data?.message || 'Gagal mengambil data dashboard.';
      console.error(e);
    } finally {
      loading = false;
    }
  }

  onMount(fetchDashboard);
  onDestroy(destroyCharts);

  // badge warna status
  function badge(status: string) {
    switch (status) {
      case 'Complete': return 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
      case 'Ongoing':  return 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200';
      case 'Prospect': return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200';
      case 'Cancel':   return 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
      default:         return 'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200';
    }
  }
</script>

<svelte:head>
  <title>Dashboard - Indogreen</title>
</svelte:head>

{#if loading}
  <p class="text-gray-900 dark:text-white">Memuat data dashboard...</p>
{:else if error}
  <p class="text-red-500">{error}</p>
{:else}
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Dashboard</h1>
      <a href="/projects"
        class="px-4 py-2 rounded-md text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700">
        Lihat Semua Project
      </a>
    </div>

    <!-- Metric cards -->
    <!-- Metric cards Project (Rapih) -->
    <!-- <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-gray-400">Total Project</div>
        <div class="mt-2 text-3xl font-bold text-gray-400">{totals.total_projects}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-blue-400">Ongoing</div>
        <div class="mt-2 text-3xl font-bold text-blue-400">{totals.ongoing}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-yellow-400">Prospect</div>
        <div class="mt-2 text-3xl font-bold text-yellow-400">{totals.prospect}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-emerald-400">Complete</div>
        <div class="mt-2 text-3xl font-bold text-emerald-400">{totals.complete}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-red-400">Cancel</div>
        <div class="mt-2 text-3xl font-bold text-red-400">{totals.cancel}</div>
      </div>
    </div> -->
    <!-- Metric cards Project (Custom) -->
    <div class="grid grid-cols-2 md:grid-cols-2 xl:grid-cols-6 gap-4">
      <div class="col-span-2 rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-gray-400">Total Project</div>
        <div class="mt-2 text-3xl font-bold text-gray-400">{totals.total_projects}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-blue-400">Ongoing</div>
        <div class="mt-2 text-3xl font-bold text-blue-400">{totals.ongoing}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-yellow-400">Prospect</div>
        <div class="mt-2 text-3xl font-bold text-yellow-400">{totals.prospect}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-emerald-400">Complete</div>
        <div class="mt-2 text-3xl font-bold text-emerald-400">{totals.complete}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-red-400">Cancel</div>
        <div class="mt-2 text-3xl font-bold text-red-400">{totals.cancel}</div>
      </div>
    </div>
    
    <!-- Metric cards Cert Project (Rapih) -->
    <!-- <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-3 gap-4">
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-purple-400">Cert Project</div>
        <div class="mt-2 text-3xl font-bold text-purple-400">{totals.cert_projects}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-emerald-400">Sertifikat Aktif</div>
        <div class="mt-2 text-3xl font-bold text-emerald-400">{totals.cert_active}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-orange-400">Expired ≤ 30 hari</div>
        <div class="mt-2 text-3xl font-bold text-orange-400">{totals.cert_expiring_30}</div>
      </div>
    </div> -->
    <!-- Metric cards Cert Project (Custom) -->
    <div class="grid grid-cols-2 md:grid-cols-2 xl:grid-cols-4 gap-4">
      <div class="col-span-2 rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-purple-400">Cert Project</div>
        <div class="mt-2 text-3xl font-bold text-purple-400">{totals.cert_projects}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-emerald-400">Sertifikat Aktif</div>
        <div class="mt-2 text-3xl font-bold text-emerald-400">{totals.cert_active}</div>
      </div>
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-xs text-red-400">Expired ≤ 30 hari</div>
        <div class="mt-2 text-3xl font-bold text-red-400">{totals.cert_expiring_30}</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-sm font-semibold mb-3 text-gray-900 dark:text-gray-100">Tren Project 12 Bulan</div>
        <div class="w-full h-[280px]">
          <canvas bind:this={cTrend} class="w-full h-full"></canvas>
        </div>
      </div>

      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-sm font-semibold mb-3 text-gray-900 dark:text-gray-100">Distribusi Status</div>
        <div class="w-full h-[280px]">
          <canvas bind:this={cStatus} class="w-full h-full"></canvas>
        </div>
      </div>

      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-sm font-semibold mb-3 text-gray-900 dark:text-gray-100">Project per Kategori</div>
        <div class="w-full h-[280px]">
          <canvas bind:this={cKategori} class="w-full h-full"></canvas>
        </div>
      </div>

      <div class="rounded-xl p-4 bg-white dark:bg-neutral-900 shadow">
        <div class="text-sm font-semibold mb-3 text-gray-900 dark:text-gray-100">Top Customer (by Project)</div>
        <div class="w-full h-[280px]">
          <canvas bind:this={cTopCust} class="w-full h-full"></canvas>
        </div>
      </div>
    </div>

    <!-- Project Terbaru -->
    <div class="mt-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Project Terbaru</h2>
        <a href="/projects" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Lihat semua</a>
      </div>
      <div class="mt-3 bg-white dark:bg-black shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
          {#if latestProjects.length === 0}
            <li class="px-4 py-4 sm:px-6">
              <p class="text-sm text-gray-500 dark:text-gray-300">Belum ada project terbaru.</p>
            </li>
          {:else}
            {#each latestProjects as project (project.id)}
              <li>
                <a href={`/projects/${project.id}`} class="block hover:bg-gray-50 dark:hover:bg-neutral-950 px-4 py-4 sm:px-6">
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">{project.name}</p>
                    <div class="ml-2 flex-shrink-0 flex gap-2">
                      <span class={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${badge(project.status)}`}>{project.status}</span>
                      {#if project.is_cert_projects}
                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">Certificate</span>
                      {/if}
                    </div>
                  </div>
                  <div class="mt-1 sm:flex sm:justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                      Customer: {project.mitra?.nama || '-'}
                      | Deskripsi: {project.description?.substring(0, 60)}{project.description?.length > 60 ? '...' : ''}
                    </p>
                    <p class="mt-1 sm:mt-0 text-sm text-gray-500 dark:text-gray-300">
                      Mulai: {new Date(project.start_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}
                    </p>
                  </div>
                </a>
              </li>
            {/each}
          {/if}
        </ul>
      </div>
    </div>
  </div>
{/if}
