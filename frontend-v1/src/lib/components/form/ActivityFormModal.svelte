<script lang="ts">
  import axiosClient from '$lib/axiosClient';
  import FileAttachment from '$lib/components/FileAttachment.svelte';
  import Modal from '$lib/components/Modal.svelte';
  import { FINANCE_CATEGORIES } from '$lib/constants';
  import { extractApiErrors } from '$lib/utils/errors';
  import { formatFileSize } from '$lib/utils/formatters';
  import type {
    ExistingAttachment,
    Project
  } from '$lib/types';

  type ActivityModalForm = {
    name: string;
    short_desc: string;
    description: string;
    project_id: string | number | '';
    kategori: string;
    activity_date: string;
    jenis: string;
    mitra_id?: number | string | '' | null;
    from?: string;
    to?: string;
    value: number | string;
    attachments: File[];
    attachment_names: string[];
    attachment_descriptions: string[];
    existing_attachments?: Array<
      Omit<ExistingAttachment, 'path' | 'url'> & { path?: string; url?: string | null }
    >;
    removed_existing_ids?: number[];
  };
  type ActivityModalEditForm = ActivityModalForm & {
    existing_attachments: ExistingAttachment[];
    removed_existing_ids: number[];
  };

  type ProjectOption = Pick<Project, 'id' | 'name' | 'mitra_id' | 'mitra'>;
  type VendorOption = { id: number; nama: string };
  type ExtractedActivity = Partial<
    Pick<ActivityModalForm, 'name' | 'short_desc' | 'description' | 'kategori' | 'jenis' | 'activity_date' | 'from' | 'to'>
  > & {
    value?: number | string | null;
    mitra_id?: number | string | null;
  };

  function makeDefaultForm(): ActivityModalForm {
    return {
      name: '',
      short_desc: '',
      description: '',
      project_id: '',
      kategori: '',
      value: 0,
      activity_date: '',
      jenis: '',
      mitra_id: '',
      from: '',
      to: '',
      attachments: [],
      attachment_names: [],
      attachment_descriptions: []
    };
  }

  /**
   * Activity form modal props. `show` and `form` are bindable for create/edit state.
   */
  let {
    show = $bindable(false),
    title = 'Form Aktivitas',
    submitLabel = 'Simpan',
    idPrefix = 'activity',
    allowRemoveAttachment = true,
    showProjectSelect = true,
    form = $bindable(makeDefaultForm()),
    projects = [],
    vendors = [],
    activityKategoriList = [],
    activityJenisList = [],
    onSubmit
  }: {
    show?: boolean;
    title?: string;
    submitLabel?: string;
    idPrefix?: string;
    allowRemoveAttachment?: boolean;
    showProjectSelect?: boolean;
    form?: ActivityModalForm | ActivityModalEditForm;
    projects?: ProjectOption[];
    vendors?: VendorOption[];
    activityKategoriList?: string[];
    activityJenisList?: string[];
    onSubmit?: () => Promise<void> | void;
  } = $props();

  const MAX_SHORT_DESC = 80;
  const financeCategories: readonly string[] = FINANCE_CATEGORIES;
  let isSubmitting = $state(false);
  let isExtracting = $state(false);
  let aiFileInput: HTMLInputElement | undefined = $state();
  let toastMessage = $state('');
  let toastType = $state<'success' | 'error'>('success');
  let toastVisible = $state(false);
  let toastTimer: ReturnType<typeof setTimeout> | undefined;
  let selectedProject = $derived(projects.find((project) => project.id === Number(form.project_id)));
  let showValueInput = $derived(Boolean(form.kategori && financeCategories.includes(form.kategori)));
  let shortDescLen = $derived(form.short_desc?.length ?? 0);
  let formattedValuePreview = $derived(
    new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 2
    }).format(Number(form.value || 0))
  );

  function showToast(message: string, type: 'success' | 'error' = 'success'): void {
    if (toastTimer) clearTimeout(toastTimer);
    toastMessage = message;
    toastType = type;
    toastVisible = true;
    toastTimer = setTimeout(() => {
      toastVisible = false;
    }, 5000);
  }

  function triggerAIFileInput(): void {
    aiFileInput?.click();
  }

  async function handleAIAutoFill(file: File): Promise<void> {
    isExtracting = true;
    try {
      const formData = new FormData();
      formData.append('action', 'extract');
      formData.append('document', file);
      if (form.project_id) formData.append('project_id', String(form.project_id));

      const response = await axiosClient.post<{ data?: ExtractedActivity }>('/activities', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      const extracted = response.data.data;
      if (!extracted) throw new Error('Respons AI tidak valid.');

      if (extracted.name) form.name = extracted.name;
      if (extracted.short_desc) form.short_desc = extracted.short_desc.slice(0, MAX_SHORT_DESC);
      if (extracted.description) form.description = extracted.description;
      if (extracted.kategori) form.kategori = extracted.kategori;
      if (extracted.jenis) form.jenis = extracted.jenis;
      if (extracted.value !== undefined && extracted.value !== null) form.value = Number(extracted.value);
      if (extracted.activity_date) form.activity_date = extracted.activity_date;
      if (extracted.from !== undefined && extracted.from !== null) form.from = extracted.from;
      if (extracted.to !== undefined && extracted.to !== null) form.to = extracted.to;
      if (extracted.mitra_id !== undefined && extracted.mitra_id !== null) form.mitra_id = extracted.mitra_id;

      showToast('Ekstraksi berhasil, silakan periksa kembali data sebelum menyimpan.', 'success');
    } catch (err: unknown) {
      showToast(extractApiErrors(err), 'error');
    } finally {
      isExtracting = false;
      if (aiFileInput) aiFileInput.value = '';
    }
  }

  function onAIFileChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) void handleAIAutoFill(file);
  }

  async function handleSubmit(): Promise<void> {
    if (isSubmitting) return;
    isSubmitting = true;
    try {
      await onSubmit?.();
    } finally {
      isSubmitting = false;
    }
  }

  function handleFormSubmit(event: SubmitEvent): void {
    event.preventDefault();
    void handleSubmit();
  }

  function trimShortDescription(): void {
    form.short_desc = (form.short_desc ?? '').slice(0, MAX_SHORT_DESC);
  }

  function removeExistingAttachment(id: number): void {
    form.removed_existing_ids = [...(form.removed_existing_ids ?? []), id];
    form.existing_attachments = (form.existing_attachments ?? []).filter((item) => item.id !== id);
  }

  $effect(() => {
    if (!showValueInput) form.value = 0;
  });
</script>

<input
  bind:this={aiFileInput}
  type="file"
  accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt"
  class="hidden"
  onchange={onAIFileChange}
  aria-hidden="true"
  tabindex="-1"
  id={`${idPrefix}_ai_file_input`}
/>

{#if toastVisible}
  <div
    role="alert"
    aria-live="polite"
    class="fixed right-5 bottom-5 z-[9999] max-w-sm rounded-xl px-5 py-3.5 text-sm font-medium text-white shadow-2xl transition-all duration-300 {toastType === 'success' ? 'bg-emerald-600' : 'bg-red-600'}"
  >
    {toastMessage}
    <button
      type="button"
      class="ml-3 font-bold opacity-70 hover:opacity-100"
      onclick={() => {
        toastVisible = false;
      }}
      aria-label="Tutup notifikasi"
    >
      &times;
    </button>
  </div>
{/if}

<Modal bind:show={show} {title} maxWidth="max-w-xl">
  {#if form.project_id}
    <h1 class="text-center text-base font-bold tracking-tight text-gray-900 dark:text-white">
      Project : {selectedProject?.name}
    </h1>
    <h1 class="mb-4 text-center text-base font-bold tracking-tight text-gray-900 dark:text-white">
      Customer : {selectedProject?.mitra?.nama}
    </h1>
  {/if}

  <div class="mb-5 flex items-center justify-center">
    <button
      type="button"
      id={`${idPrefix}_ai_autofill_btn`}
      onclick={triggerAIFileInput}
      disabled={isExtracting || isSubmitting}
      aria-busy={isExtracting}
      class="group relative inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:from-violet-500 hover:to-indigo-500 hover:shadow-lg hover:shadow-violet-500/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600 disabled:translate-y-0 disabled:cursor-not-allowed disabled:opacity-60 disabled:shadow-none"
    >
      {#if isExtracting}
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4"></path>
        </svg>
        <span>Mengekstrak dokumen...</span>
      {:else}
        <span>Auto-Fill with AI</span>
      {/if}
    </button>
  </div>

  {#if isExtracting}
    <div class="mb-4 flex items-center gap-2 rounded-lg border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm text-violet-700 dark:border-violet-700 dark:bg-violet-900/20 dark:text-violet-300">
      <span>AI sedang menganalisis dokumen Anda. Ini mungkin memerlukan beberapa detik...</span>
    </div>
  {/if}

  <form onsubmit={handleFormSubmit} autocomplete="off">
    <fieldset disabled={isSubmitting || isExtracting} class="space-y-4">
      <div>
        <label for={`${idPrefix}_name`} class="block text-sm/6 font-medium text-gray-900 dark:text-white">Nama Aktivitas</label>
        <input id={`${idPrefix}_name`} type="text" bind:value={form.name} required placeholder="Masukkan nama aktivitas" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700 dark:placeholder:text-gray-500" />
      </div>

      {#if showProjectSelect}
        <div>
          <label for={`${idPrefix}_project_id`} class="block text-sm/6 font-medium text-gray-900 dark:text-white">Project</label>
          <select id={`${idPrefix}_project_id`} bind:value={form.project_id} required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700">
            <option value="">Pilih Project</option>
            {#each projects as project (project.id)}<option value={project.id}>{project.name}</option>{/each}
          </select>
        </div>
      {/if}

      <div>
        <label for={`${idPrefix}_jenis`} class="block text-sm/6 font-medium text-gray-900 dark:text-white">Jenis</label>
        <select id={`${idPrefix}_jenis`} bind:value={form.jenis} required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700">
          <option value="">Pilih Jenis</option>
          {#each activityJenisList as jenis}<option value={jenis}>{jenis}</option>{/each}
        </select>
      </div>

      {#if form.jenis === 'Customer'}
        <p class="text-sm text-gray-500 dark:text-gray-400">Customer akan otomatis dipilih berdasarkan Project.</p>
      {:else if form.jenis === 'Vendor'}
        <div>
          <label for={`${idPrefix}_mitra_id_vendor`} class="block text-sm/6 font-medium text-gray-900 dark:text-white">Vendor</label>
          <select id={`${idPrefix}_mitra_id_vendor`} bind:value={form.mitra_id} required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700">
            <option value="">Pilih Vendor</option>
            {#each vendors as vendor (vendor.id)}<option value={vendor.id}>{vendor.nama}</option>{/each}
          </select>
        </div>
      {/if}

      <div>
        <label for={`${idPrefix}_kategori`} class="block text-sm/6 font-medium text-gray-900 dark:text-white">Kategori</label>
        <select id={`${idPrefix}_kategori`} bind:value={form.kategori} required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700">
          <option value="">Pilih Kategori</option>
          {#each activityKategoriList as kategori}<option value={kategori}>{kategori}</option>{/each}
        </select>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label for={`${idPrefix}_from`} class="block text-sm/6 font-medium text-gray-900 dark:text-white">From (Optional)</label>
          <input id={`${idPrefix}_from`} bind:value={form.from} placeholder="Dari" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700 dark:placeholder:text-gray-500" />
        </div>
        <div>
          <label for={`${idPrefix}_to`} class="block text-sm/6 font-medium text-gray-900 dark:text-white">To (Optional)</label>
          <input id={`${idPrefix}_to`} bind:value={form.to} placeholder="Ke" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700 dark:placeholder:text-gray-500" />
        </div>
      </div>

      <div>
        <label for={`${idPrefix}_short_desc`} class="block text-sm/6 font-medium text-gray-900 dark:text-white">Deskripsi Singkat (Max: 80 Karakter)</label>
        <textarea id={`${idPrefix}_short_desc`} bind:value={form.short_desc} oninput={trimShortDescription} rows="2" required maxlength={MAX_SHORT_DESC} placeholder="Masukkan deskripsi singkat" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700 dark:placeholder:text-gray-500"></textarea>
        <div class="mt-1 text-right text-xs text-gray-500 dark:text-gray-400">{shortDescLen}/{MAX_SHORT_DESC}</div>
      </div>

      <div>
        <label for={`${idPrefix}_description`} class="block text-sm/6 font-medium text-gray-900 dark:text-white">Deskripsi</label>
        <textarea id={`${idPrefix}_description`} bind:value={form.description} rows="4" required placeholder="Masukkan deskripsi aktivitas" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700 dark:placeholder:text-gray-500"></textarea>
      </div>

      {#if showValueInput}
        <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4 transition-all duration-300 ease-in-out dark:border-emerald-800 dark:bg-emerald-900/20">
          <label for={`${idPrefix}_value`} class="mb-1 block text-sm font-medium text-emerald-800 dark:text-emerald-400">Nilai / Value (Rp)</label>
          <input id={`${idPrefix}_value`} type="number" step="0.01" min="0" bind:value={form.value} class="block w-full rounded-md border-emerald-300 bg-white py-1 pl-3 text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:border-emerald-700 dark:bg-neutral-900 dark:text-white" placeholder="0.00" required={showValueInput} />
          <div class="mt-1 flex items-start justify-between"><p class="text-xs text-emerald-600 dark:text-emerald-500">Wajib diisi (Angka saja).</p><p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Terbaca: {formattedValuePreview}</p></div>
        </div>
      {/if}

      <div>
        <label for={`${idPrefix}_activity_date`} class="block text-sm/6 font-medium text-gray-900 dark:text-white">Tanggal Aktivitas</label>
        <input id={`${idPrefix}_activity_date`} type="date" bind:value={form.activity_date} required class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700" />
      </div>

      <FileAttachment id={`${idPrefix}_attachments`} label="Lampiran" bind:files={form.attachments} bind:fileNames={form.attachment_names} bind:fileDescriptions={form.attachment_descriptions} maxFiles={10} showRemoveButton={allowRemoveAttachment} />

      {#if form.existing_attachments && form.existing_attachments.length > 0}
        <div class="mt-3 space-y-3">
          <p class="text-sm font-medium text-gray-900 dark:text-white">Lampiran Lama</p>
          {#each form.existing_attachments as attachment (attachment.id)}
            <div class="space-y-2 rounded border px-3 py-2 text-sm dark:border-gray-700">
              <a class="truncate text-indigo-600 hover:underline dark:text-indigo-400" href={attachment.url ?? attachment.path} target="_blank" rel="noreferrer">{attachment.original_name ?? attachment.name}</a>
              <input type="text" bind:value={attachment.name} required placeholder="Nama lampiran" class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              <input type="text" bind:value={attachment.description} placeholder="Deskripsi lampiran" class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
              <div class="flex items-center justify-end gap-3">
                {#if attachment.size}<span class="text-gray-500 dark:text-gray-400">{formatFileSize(attachment.size)}</span>{/if}
                <button type="button" class="text-red-600 hover:text-red-700" onclick={() => removeExistingAttachment(attachment.id)}>Hapus</button>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </fieldset>

    <div class="mt-6">
      <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:opacity-60" disabled={isSubmitting || isExtracting} aria-busy={isSubmitting}>
        {#if isSubmitting}
          <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4"></path></svg>
          <span>Menyimpan...</span>
        {:else}
          {submitLabel}
        {/if}
      </button>
    </div>
  </form>
</Modal>

<style>
  :global(.break-all) {
    word-break: break-all;
    overflow-wrap: anywhere;
  }

  input[type='number']::-webkit-inner-spin-button,
  input[type='number']::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  input[type='number'] {
    appearance: textfield;
    -moz-appearance: textfield;
  }
</style>
