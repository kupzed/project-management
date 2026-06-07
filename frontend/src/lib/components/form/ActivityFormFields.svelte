<script lang="ts">
  import type { ActivityModalForm, ProjectOption, VendorOption } from './activity-form';

  /** Bindable core fields shared by activity create and edit forms. */
  let {
    form = $bindable(),
    idPrefix,
    showProjectSelect,
    projects,
    vendors,
    activityKategoriList,
    activityJenisList,
    maxShortDescription,
    shortDescriptionLength,
    showValueInput,
    formattedValuePreview,
    onTrimShortDescription
  }: {
    form: ActivityModalForm;
    idPrefix: string;
    showProjectSelect: boolean;
    projects: ProjectOption[];
    vendors: VendorOption[];
    activityKategoriList: string[];
    activityJenisList: string[];
    maxShortDescription: number;
    shortDescriptionLength: number;
    showValueInput: boolean;
    formattedValuePreview: string;
    onTrimShortDescription: () => void;
  } = $props();
</script>

<div>
  <label for={`${idPrefix}_name`} class="block text-sm/6 font-medium text-gray-900 dark:text-white">
    Nama Aktivitas
  </label>
  <input
    id={`${idPrefix}_name`}
    type="text"
    bind:value={form.name}
    required
    placeholder="Masukkan nama aktivitas"
    class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700 dark:placeholder:text-gray-500"
  />
</div>

{#if showProjectSelect}
  <div>
    <label
      for={`${idPrefix}_project_id`}
      class="block text-sm/6 font-medium text-gray-900 dark:text-white"
    >
      Project
    </label>
    <select
      id={`${idPrefix}_project_id`}
      bind:value={form.project_id}
      required
      class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700"
    >
      <option value="">Pilih Project</option>
      {#each projects as project (project.id)}
        <option value={project.id}>{project.name}</option>
      {/each}
    </select>
  </div>
{/if}

<div>
  <label for={`${idPrefix}_jenis`} class="block text-sm/6 font-medium text-gray-900 dark:text-white"
    >Jenis</label
  >
  <select
    id={`${idPrefix}_jenis`}
    bind:value={form.jenis}
    required
    class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700"
  >
    <option value="">Pilih Jenis</option>
    {#each activityJenisList as jenis (jenis)}
      <option value={jenis}>{jenis}</option>
    {/each}
  </select>
</div>

{#if form.jenis === 'Customer'}
  <p class="text-sm text-gray-500 dark:text-gray-400">
    Customer akan otomatis dipilih berdasarkan Project.
  </p>
{:else if form.jenis === 'Vendor'}
  <div>
    <label
      for={`${idPrefix}_mitra_id_vendor`}
      class="block text-sm/6 font-medium text-gray-900 dark:text-white"
    >
      Vendor
    </label>
    <select
      id={`${idPrefix}_mitra_id_vendor`}
      bind:value={form.mitra_id}
      required
      class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700"
    >
      <option value="">Pilih Vendor</option>
      {#each vendors as vendor (vendor.id)}
        <option value={vendor.id}>{vendor.nama}</option>
      {/each}
    </select>
  </div>
{/if}

<div>
  <label
    for={`${idPrefix}_kategori`}
    class="block text-sm/6 font-medium text-gray-900 dark:text-white">Kategori</label
  >
  <select
    id={`${idPrefix}_kategori`}
    bind:value={form.kategori}
    required
    class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700"
  >
    <option value="">Pilih Kategori</option>
    {#each activityKategoriList as kategori (kategori)}
      <option value={kategori}>{kategori}</option>
    {/each}
  </select>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
  <div>
    <label
      for={`${idPrefix}_from`}
      class="block text-sm/6 font-medium text-gray-900 dark:text-white">From (Optional)</label
    >
    <input
      id={`${idPrefix}_from`}
      bind:value={form.from}
      placeholder="Dari"
      class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700 dark:placeholder:text-gray-500"
    />
  </div>
  <div>
    <label for={`${idPrefix}_to`} class="block text-sm/6 font-medium text-gray-900 dark:text-white"
      >To (Optional)</label
    >
    <input
      id={`${idPrefix}_to`}
      bind:value={form.to}
      placeholder="Ke"
      class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700 dark:placeholder:text-gray-500"
    />
  </div>
</div>

<div>
  <label
    for={`${idPrefix}_short_desc`}
    class="block text-sm/6 font-medium text-gray-900 dark:text-white"
  >
    Deskripsi Singkat (Max: {maxShortDescription} Karakter)
  </label>
  <textarea
    id={`${idPrefix}_short_desc`}
    bind:value={form.short_desc}
    oninput={onTrimShortDescription}
    rows="2"
    required
    maxlength={maxShortDescription}
    placeholder="Masukkan deskripsi singkat"
    class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700 dark:placeholder:text-gray-500"
  ></textarea>
  <div class="mt-1 text-right text-xs text-gray-500 dark:text-gray-400">
    {shortDescriptionLength}/{maxShortDescription}
  </div>
</div>

<div>
  <label
    for={`${idPrefix}_description`}
    class="block text-sm/6 font-medium text-gray-900 dark:text-white">Deskripsi</label
  >
  <textarea
    id={`${idPrefix}_description`}
    bind:value={form.description}
    rows="4"
    required
    placeholder="Masukkan deskripsi aktivitas"
    class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700 dark:placeholder:text-gray-500"
  ></textarea>
</div>

{#if showValueInput}
  <div
    class="rounded-lg border border-emerald-100 bg-emerald-50 p-4 transition-all duration-300 ease-in-out dark:border-emerald-800 dark:bg-emerald-900/20"
  >
    <label
      for={`${idPrefix}_value`}
      class="mb-1 block text-sm font-medium text-emerald-800 dark:text-emerald-400"
    >
      Nilai / Value (Rp)
    </label>
    <input
      id={`${idPrefix}_value`}
      type="number"
      step="0.01"
      min="0"
      bind:value={form.value}
      class="block w-full rounded-md border-emerald-300 bg-white py-1 pl-3 text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:border-emerald-700 dark:bg-neutral-900 dark:text-white"
      placeholder="0.00"
      required
    />
    <div class="mt-1 flex items-start justify-between">
      <p class="text-xs text-emerald-600 dark:text-emerald-500">Wajib diisi (Angka saja).</p>
      <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">
        Terbaca: {formattedValuePreview}
      </p>
    </div>
  </div>
{/if}

<div>
  <label
    for={`${idPrefix}_activity_date`}
    class="block text-sm/6 font-medium text-gray-900 dark:text-white"
  >
    Tanggal Aktivitas
  </label>
  <input
    id={`${idPrefix}_activity_date`}
    type="date"
    bind:value={form.activity_date}
    required
    class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700"
  />
</div>

<style>
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
