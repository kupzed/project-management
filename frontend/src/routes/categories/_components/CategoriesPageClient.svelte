<script lang="ts">
  import { onMount } from 'svelte';
  import Modal from '$lib/components/Modal.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import {
    createCategory,
    deleteCategory as deleteCategoryRecord,
    fetchCategories as fetchCategoryRecords,
    updateCategory,
    type CategoryForm
  } from '$lib/services/inventoryService';
  import { userPermissions } from '$lib/stores/permissions';
  import { showError, showSuccess } from '$lib/utils/toast';
  import { extractApiErrors } from '$lib/utils/errors';
  import { CATEGORY_TYPE_OPTIONS, type Category, type CategoryType } from '$lib/inventory';

  let categories = $state<Category[]>([]);
  let loading = $state(true);
  let error = $state('');
  let search = $state('');
  let typeFilter = $state<'' | CategoryType>('');
  let currentPage = $state(1);
  let lastPage = $state(1);
  let totalItems = $state(0);
  let perPage = $state(25);
  const perPageOptions = [10, 25, 50, 100];

  let showModal = $state(false);
  let editingCategory = $state<Category | null>(null);
  let form = $state<CategoryForm>({
    name: '',
    type: 'item'
  });

  let canCreate = $derived(($userPermissions ?? []).includes('category-create'));
  let canUpdate = $derived(($userPermissions ?? []).includes('category-update'));
  let canDelete = $derived(($userPermissions ?? []).includes('category-delete'));

  async function fetchCategories() {
    loading = true;
    error = '';

    try {
      const result = await fetchCategoryRecords({
        search: search || undefined,
        type: typeFilter || undefined,
        page: currentPage,
        per_page: perPage
      });

      categories = result.data;
      currentPage = result.meta.current_page;
      lastPage = result.meta.last_page;
      totalItems = result.meta.total;
    } catch (err) {
      error = extractApiErrors(err);
    } finally {
      loading = false;
    }
  }

  let searchTimer: ReturnType<typeof setTimeout>;
  function handleSearchDebounced() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      currentPage = 1;
      fetchCategories();
    }, 500);
  }

  function handleFilterChange() {
    currentPage = 1;
    fetchCategories();
  }

  function openCreateModal() {
    if (!canCreate) return;
    editingCategory = null;
    form = { name: '', type: 'item' };
    showModal = true;
  }

  function openEditModal(category: Category) {
    if (!canUpdate) return;
    editingCategory = category;
    form = {
      name: category.name,
      type: category.type
    };
    showModal = true;
  }

  async function submitCategory() {
    try {
      if (editingCategory) {
        await updateCategory(editingCategory.id, form);
        showSuccess('Kategori berhasil diperbarui.');
      } else {
        await createCategory(form);
        showSuccess('Kategori berhasil ditambahkan.');
      }

      showModal = false;
      await fetchCategories();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function deleteCategory(category: Category) {
    if (!canDelete) return;
    const confirmed = await confirm({
      title: `Hapus kategori "${category.name}"?`,
      text: 'Kategori yang dihapus tidak dapat dikembalikan.',
      confirmText: 'Hapus',
      isDangerous: true
    });

    if (!confirmed) return;

    try {
      await deleteCategoryRecord(category.id);
      showSuccess('Kategori berhasil dihapus.');
      await fetchCategories();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  function typeLabel(type: CategoryType): string {
    return CATEGORY_TYPE_OPTIONS.find((option) => option.value === type)?.label ?? type;
  }

  function handleSubmit(event: SubmitEvent) {
    event.preventDefault();
    void submitCategory();
  }

  function handlePageChange(page: number) {
    currentPage = page;
    void fetchCategories();
  }

  function handlePerPageChange(nextPerPage: number) {
    perPage = nextPerPage;
    currentPage = 1;
    void fetchCategories();
  }

  onMount(fetchCategories);
</script>

<svelte:head>
  <title>Kategori - Indogreen</title>
</svelte:head>

<div class="flex flex-col gap-5">
  <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kategori</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Kelola kategori terpusat untuk item dan kebutuhan data berikutnya.
      </p>
    </div>

    {#if canCreate}
      <button
        type="button"
        onclick={openCreateModal}
        class="inline-flex h-10 items-center justify-center rounded-md bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
      >
        Tambah Kategori
      </button>
    {/if}
  </div>

  <div
    class="grid gap-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm md:grid-cols-[minmax(0,1fr)_220px] dark:border-gray-800 dark:bg-black"
  >
    <div class="relative">
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
      <input
        type="text"
        bind:value={search}
        oninput={handleSearchDebounced}
        placeholder="Cari nama kategori..."
        class="h-10 w-full rounded-md border border-gray-300 bg-white pr-3 pl-10 text-sm text-gray-900 placeholder-gray-500 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      />
    </div>

    <select
      bind:value={typeFilter}
      onchange={handleFilterChange}
      class="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm font-medium text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Semua tipe</option>
      {#each CATEGORY_TYPE_OPTIONS as option (option.value)}
        <option value={option.value}>{option.label}</option>
      {/each}
    </select>
  </div>

  <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-black">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[720px] divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-neutral-900">
          <tr>
            <th
              class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Nama
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Tipe
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          {#if loading}
            <tr>
              <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500"
                >Memuat kategori...</td
              >
            </tr>
          {:else if error}
            <tr>
              <td colspan="3" class="px-4 py-8 text-center text-sm text-red-600">{error}</td>
            </tr>
          {:else if categories.length === 0}
            <tr>
              <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">
                Belum ada kategori.
              </td>
            </tr>
          {:else}
            {#each categories as category (category.id)}
              <tr class="hover:bg-gray-50 dark:hover:bg-neutral-950">
                <td class="px-4 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                  {category.name}
                </td>
                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                  <span
                    class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200"
                  >
                    {typeLabel(category.type)}
                  </span>
                </td>
                <td class="px-4 py-4 text-right text-sm">
                  <div class="inline-flex justify-end w-full">
                    <RowActionButtons
                      label={category.name}
                      canEdit={canUpdate}
                      {canDelete}
                      onEdit={() => openEditModal(category)}
                      onDelete={() => void deleteCategory(category)}
                    />
                  </div>
                </td>
              </tr>
            {/each}
          {/if}
        </tbody>
      </table>
    </div>

    <Pagination
      {currentPage}
      {lastPage}
      {totalItems}
      itemsPerPage={perPage}
      {perPageOptions}
      onPageChange={handlePageChange}
      onPerPageChange={handlePerPageChange}
    />
  </div>
</div>

<Modal bind:show={showModal} title={editingCategory ? 'Edit Kategori' : 'Tambah Kategori'}>
  <form class="space-y-4" onsubmit={handleSubmit}>
    <div>
      <label
        for="category-name"
        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        Nama
      </label>
      <input
        id="category-name"
        type="text"
        bind:value={form.name}
        required
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      />
    </div>

    <div>
      <label
        for="category-type"
        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        Tipe
      </label>
      <select
        id="category-type"
        bind:value={form.type}
        required
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      >
        {#each CATEGORY_TYPE_OPTIONS as option (option.value)}
          <option value={option.value}>{option.label}</option>
        {/each}
      </select>
    </div>

    <div class="flex justify-end gap-2 pt-2">
      <button
        type="button"
        onclick={() => (showModal = false)}
        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-neutral-900"
      >
        Batal
      </button>
      <button
        type="submit"
        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
      >
        Simpan
      </button>
    </div>
  </form>
</Modal>
