<script lang="ts">
  import axiosClient from '$lib/axiosClient';
  import { createEventDispatcher } from 'svelte';
  import { userPermissions } from '$lib/stores/permissions';

  export let item: any = null;

  // Permission
  let canUpdateFinance = false;

  $: {
    const perms = $userPermissions ?? [];
    canUpdateFinance = perms.includes('finance-update');
  }

  const dispatch = createEventDispatcher();

  const formatRupiah = (val: number) =>
    new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(val ?? 0);

  const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return dateStr;
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
  };

  type NormalizedAttachment = {
    url: string;
    displayName: string;
    sizeLabel?: string;
    desc?: string;
  };

  function formatBytes(bytes?: number | null) {
    if (bytes === undefined || bytes === null || !Number.isFinite(Number(bytes))) return undefined;
    let n = Number(bytes);
    const units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;
    while (n >= 1024 && i < units.length - 1) {
      n /= 1024;
      i++;
    }
    const rounded = i === 0 ? Math.round(n) : n < 10 ? n.toFixed(1) : Math.round(n).toString();
    return `${rounded}${units[i]}`;
  }

  function normalizeAttachments(raw: any): NormalizedAttachment[] {
    if (!raw) return [];
    const toAttachment = (att: any): NormalizedAttachment | null => {
      if (!att) return null;
      if (typeof att === 'string') {
        const filename = att.split('/').pop() ?? att;
        return { url: att, displayName: filename };
      }
      const path = att?.path ?? att?.file_path ?? '';
      const url = att?.url ?? path;
      if (!url) return null;
      const displayName = att?.name ?? att?.label ?? (path.split('/').pop() ?? 'Lampiran');
      return {
        url,
        displayName,
        desc: att?.description ?? att?.desc ?? undefined,
        sizeLabel: att?.sizeLabel ?? formatBytes(att?.size)
      };
    };
    return (Array.isArray(raw) ? raw : [raw]).map(toAttachment).filter(Boolean) as NormalizedAttachment[];
  }

  let submitting = false;
  let valueInput = 0;
  let errorMessage = '';
  let successMessage = '';
  let lastActivityId: number | null = null;
  let showValueEditor = false;

  $: activity = item;
  $: attachments = normalizeAttachments(activity?.attachments);

  $: if (activity?.id && activity.id !== lastActivityId) {
    lastActivityId = activity.id;
    valueInput = Number(activity.value ?? 0) || 0;
    errorMessage = '';
    successMessage = '';
    showValueEditor = false;
  }

  async function handleSave() {
    if (!canUpdateFinance) {
      console.warn('User lacks finance-update permission');
      return;
    }
    if (!activity?.id) return;
    submitting = true;
    errorMessage = '';
    successMessage = '';
    try {
      const payload = { value: Number(valueInput) || 0 };
      const { data } = await axiosClient.put(`/finance/${activity.id}`, payload);
      successMessage = 'Nilai berhasil diperbarui';
      valueInput = Number(data.data.value ?? payload.value);
      dispatch('saved', {
        activityId: activity.id,
        value: data.data.value,
        value_formatted: data.meta?.value_formatted,
        item: data.data
      });
    } catch (err: any) {
      errorMessage = err?.response?.data?.message ?? 'Gagal memperbarui nilai';
    } finally {
      submitting = false;
    }
  }
</script>

{#if activity}
  <div class="flex flex-col gap-6 py-4">
    <section class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 dark:border-white/10 dark:bg-white/5">
      <div class="flex flex-col gap-2">
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Kategori</p>
            <p class="font-semibold text-gray-900 dark:text-white">{activity.kategori}</p>
          </div>
          <span class="rounded-full bg-gray-900/5 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-200">
            {activity.activity_date}
          </span>
        </div>
        <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">{activity.name}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">{activity.short_desc || 'Tidak ada deskripsi singkat.'}</p>
      </div>
    </section>

    <section class="rounded-xl border border-gray-100 p-4 shadow-sm dark:border-white/10 dark:bg-black/40">
      <div class="flex flex-col gap-4">
        <header class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Nilai / Value</p>
            <!-- <p class="text-base font-semibold text-gray-900 dark:text-white">{formatRupiah(valueInput)}</p> -->
            <p class="text-base font-semibold text-gray-900 dark:text-white">{formatRupiah(activity.value)}</p>
          </div>
          {#if canUpdateFinance}
            <button
              type="button"
              class="inline-flex items-center gap-1 rounded-md border border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-700 transition hover:bg-gray-50 hover:text-emerald-700 dark:border-white/15 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-emerald-300"
              on:click={() => (showValueEditor = !showValueEditor)}
              aria-expanded={showValueEditor}
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              {showValueEditor ? 'Batal' : 'Edit'}
            </button>
          {/if}
        </header>

        {#if showValueEditor}
          <div class="flex flex-col gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200" for="value-input">
              (IDR)
            </label>
            <div class="flex flex-col gap-3 sm:flex-row">
              <input
                id="value-input"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-gray-700 dark:bg-neutral-900 dark:text-white dark:focus:border-emerald-400"
                type="number"
                min="0"
                step="1"
                bind:value={valueInput}
              />
              <button
                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                type="button"
                on:click={handleSave}
                disabled={submitting}
              >
                {#if submitting}
                  Menyimpan...
                {:else}
                  Simpan Nilai
                {/if}
              </button>
            </div>
            {#if errorMessage}
              <p class="text-sm text-red-600 dark:text-red-400">{errorMessage}</p>
            {/if}
            {#if successMessage}
              <p class="text-sm text-emerald-600 dark:text-emerald-400">{successMessage}</p>
            {/if}
          </div>
        {/if}
      </div>
    </section>

    <section class="rounded-xl border border-gray-100 p-4 dark:border-white/10 dark:bg-black/30">
      <div class="space-y-4">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Informasi Aktivitas</h3>
        <dl class="grid grid-cols-1 gap-4 text-sm text-gray-700 dark:text-gray-200">
          <div>
            <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Tanggal Aktivitas</dt>
            <dd class="font-medium">{formatDate(activity.activity_date)}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Project</dt>
            <dd class="font-medium">
              {#if activity.project}
                <a href={`/projects/${activity.project.id}`} class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                  {activity.project.name}
                </a>
              {:else}
                <span class="text-gray-500 dark:text-gray-400">Project tidak ditemukan</span>
              {/if}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Jenis</dt>
            <dd class="font-medium">{activity.jenis}</dd>
          </div>
          {#if (activity.jenis === 'Customer' || activity.jenis === 'Vendor') && activity.mitra}
            <div>
              <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">{activity.jenis}</dt>
              <dd class="font-medium">{activity.mitra.nama}</dd>
            </div>
          {/if}
          <div>
            <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Deskripsi</dt>
            <dd class="font-medium">{activity.description || '-'}</dd>
          </div>
        </dl>
      </div>
    </section>

    <section class="rounded-xl border border-dashed border-gray-200 p-4 dark:border-white/15">
      <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Lampiran</h3>
        {#if attachments.length}
          <ul role="list" class="divide-y divide-gray-100 dark:divide-white/5 rounded-md border border-gray-200/80 dark:border-white/20">
            {#each attachments as file (file.url)}
              <li class="py-4 pr-5 pl-4">
                <div class="flex items-start gap-3">
                  <!-- Icon -->
                  <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="w-5 h-5 shrink-0 text-gray-500 mt-0.5">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M15.621 4.379a3 3 0 0 0-4.242 0l-7 7a3 3 0 0 0 4.241 4.243h.001l.497-.5a.75.75 0 0 1 1.064 1.057l-.498.501-.002.002a4.5 4.5 0 0 1-6.364-6.364l7-7a4.5 4.5 0 0 1 6.368 6.36l-3.455 3.553A2.625 2.625 0 1 1 9.52 9.52l3.45-3.451a.75.75 0 1 1 1.061 1.06l-3.45 3.451a1.125 1.125 0 0 0 1.587 1.595l3.454-3.553a3 3 0 0 0 0-4.242Z" />
                  </svg>

                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                      <span class="font-medium text-gray-900 dark:text-white">{file.displayName}</span>
                      {#if file.sizeLabel}
                        <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{file.sizeLabel}</span>
                      {/if}
                    </div>
                    {#if file.desc}
                      <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{file.desc}</p>
                    {/if}
                  </div>

                  <div class="shrink-0">
                    <a
                    href={file.url}
                    target="_blank"
                    rel="noopener"
                    download
                    class="inline-flex items-center justify-center rounded-md p-1 hover:bg-gray-100 dark:hover:bg-white/10
                          text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300
                          focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                    aria-label={`Download ${file.displayName}`}
                    title={`Download ${file.displayName}`}
                    >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M12 3v12m0 0 4-4m-4 4-4-4"></path>
                      <path d="M5 21h14"></path>
                    </svg>
                    <span class="sr-only">Download</span>
                    </a>
                  </div>
                </div>
              </li>
            {/each}
          </ul>
        {:else}
          <span class="text-gray-500 dark:text-gray-400">Tidak ada file</span>
        {/if}
    </section>
  </div>
{:else}
  <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
    Pilih data keuangan untuk melihat detail.
  </div>
{/if}
