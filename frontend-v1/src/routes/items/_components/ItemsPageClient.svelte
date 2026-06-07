<script lang="ts">
  import { onMount } from 'svelte';
  import Modal from '$lib/components/Modal.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import {
    createItem,
    deleteItem as deleteItemRecord,
    fetchCategories,
    fetchItems as fetchItemRecords,
    updateItem,
    type ItemFormPayload
  } from '$lib/services/inventoryService';
  import { userPermissions } from '$lib/stores/permissions';
  import { showError, showSuccess } from '$lib/utils/toast';
  import { extractApiErrors } from '$lib/utils/errors';
  import { type Category, type Item } from '$lib/inventory';
  import ItemTable from './ItemTable.svelte';

  type ItemForm = {
    sku: string;
    category_id: string;
    name: string;
    unit: string;
    minimum_stock: number;
  };

  let items = $state<Item[]>([]);
  let categories = $state<Category[]>([]);
  let loading = $state(true);
  let error = $state('');
  let search = $state('');
  let categoryFilter = $state('');
  let currentPage = $state(1);
  let lastPage = $state(1);
  let totalItems = $state(0);
  let perPage = $state(25);
  const perPageOptions = [10, 25, 50, 100];

  let showModal = $state(false);
  let editingItem = $state<Item | null>(null);
  let form = $state<ItemForm>({
    sku: '',
    category_id: '',
    name: '',
    unit: 'pcs',
    minimum_stock: 0
  });

  let canCreate = $derived(($userPermissions ?? []).includes('item-create'));
  let canUpdate = $derived(($userPermissions ?? []).includes('item-update'));
  let canDelete = $derived(($userPermissions ?? []).includes('item-delete'));

  async function fetchItems() {
    loading = true;
    error = '';

    try {
      const result = await fetchItemRecords({
        search: search || undefined,
        category_id: categoryFilter || undefined,
        page: currentPage,
        per_page: perPage
      });

      items = result.data;
      currentPage = result.meta.current_page;
      lastPage = result.meta.last_page;
      totalItems = result.meta.total;
    } catch (err) {
      error = extractApiErrors(err);
    } finally {
      loading = false;
    }
  }

  async function loadCategories() {
    try {
      const result = await fetchCategories({ type: 'item', per_page: 100 });
      categories = result.data;
    } catch (err) {
      console.error('Gagal memuat kategori item:', err);
    }
  }

  let searchTimer: ReturnType<typeof setTimeout>;
  function handleSearchDebounced() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      currentPage = 1;
      fetchItems();
    }, 500);
  }

  function handleFilterChange() {
    currentPage = 1;
    fetchItems();
  }

  function resetForm() {
    form = {
      sku: '',
      category_id: categories[0]?.id ? String(categories[0].id) : '',
      name: '',
      unit: 'pcs',
      minimum_stock: 0
    };
  }

  function openCreateModal() {
    if (!canCreate) return;
    editingItem = null;
    resetForm();
    showModal = true;
  }

  function openEditModal(item: Item) {
    if (!canUpdate) return;
    editingItem = item;
    form = {
      sku: item.sku,
      category_id: String(item.category_id),
      name: item.name,
      unit: item.unit,
      minimum_stock: item.minimum_stock
    };
    showModal = true;
  }

  function payloadFromForm(): ItemFormPayload {
    return {
      sku: form.sku,
      category_id: Number(form.category_id),
      name: form.name,
      unit: form.unit,
      minimum_stock: Number(form.minimum_stock)
    };
  }

  async function submitItem() {
    try {
      const payload = payloadFromForm();

      if (editingItem) {
        await updateItem(editingItem.id, payload);
        showSuccess('Item berhasil diperbarui.');
      } else {
        await createItem(payload);
        showSuccess('Item berhasil ditambahkan.');
      }

      showModal = false;
      await fetchItems();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function deleteItem(item: Item) {
    if (!canDelete) return;
    const confirmed = await confirm({
      title: `Hapus item "${item.name}"?`,
      text: 'Item yang dihapus tidak dapat dikembalikan.',
      confirmText: 'Hapus',
      isDangerous: true
    });

    if (!confirmed) return;

    try {
      await deleteItemRecord(item.id);
      showSuccess('Item berhasil dihapus.');
      await fetchItems();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  function handleSubmit(event: SubmitEvent) {
    event.preventDefault();
    void submitItem();
  }

  function handlePageChange(page: number) {
    currentPage = page;
    void fetchItems();
  }

  function handlePerPageChange(nextPerPage: number) {
    perPage = nextPerPage;
    currentPage = 1;
    void fetchItems();
  }

  onMount(() => {
    void loadCategories();
    void fetchItems();
  });
</script>

<svelte:head>
  <title>Item - Indogreen</title>
</svelte:head>

<div class="flex flex-col gap-5">
  <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Item</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Master data material dan stok minimum per item.
      </p>
    </div>

    {#if canCreate}
      <button
        type="button"
        onclick={openCreateModal}
        class="inline-flex h-10 items-center justify-center rounded-md bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
      >
        Tambah Item
      </button>
    {/if}
  </div>

  <div
    class="grid gap-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm md:grid-cols-[minmax(0,1fr)_260px] dark:border-gray-800 dark:bg-black"
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
        placeholder="Cari item atau SKU..."
        class="h-10 w-full rounded-md border border-gray-300 bg-white pr-3 pl-10 text-sm text-gray-900 placeholder-gray-500 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      />
    </div>

    <select
      bind:value={categoryFilter}
      onchange={handleFilterChange}
      class="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm font-medium text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Semua kategori</option>
      {#each categories as category (category.id)}
        <option value={category.id}>{category.name}</option>
      {/each}
    </select>
  </div>

  <ItemTable
    {items}
    {loading}
    {error}
    {currentPage}
    {lastPage}
    {totalItems}
    {perPage}
    {perPageOptions}
    {canUpdate}
    {canDelete}
    onEdit={openEditModal}
    onDelete={deleteItem}
    onPageChange={handlePageChange}
    onPerPageChange={handlePerPageChange}
  />
</div>

<Modal bind:show={showModal} title={editingItem ? 'Edit Item' : 'Tambah Item'}>
  <form class="space-y-4" onsubmit={handleSubmit}>
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label
          for="item-sku"
          class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          SKU
        </label>
        <input
          id="item-sku"
          type="text"
          bind:value={form.sku}
          required
          class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        />
      </div>
      <div>
        <label
          for="item-unit"
          class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Unit
        </label>
        <input
          id="item-unit"
          type="text"
          bind:value={form.unit}
          required
          class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        />
      </div>
    </div>

    <div>
      <label
        for="item-name"
        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        Nama Item
      </label>
      <input
        id="item-name"
        type="text"
        bind:value={form.name}
        required
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label
          for="item-category"
          class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Kategori
        </label>
        <select
          id="item-category"
          bind:value={form.category_id}
          required
          class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        >
          <option value="" disabled>Pilih kategori</option>
          {#each categories as category (category.id)}
            <option value={category.id}>{category.name}</option>
          {/each}
        </select>
      </div>
      <div>
        <label
          for="item-minimum"
          class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Minimum Stok
        </label>
        <input
          id="item-minimum"
          type="number"
          min="0"
          bind:value={form.minimum_stock}
          required
          class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        />
      </div>
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
