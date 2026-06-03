<script lang="ts">
  import { onMount } from 'svelte';
  import axiosClient from '$lib/axiosClient';
  import Modal from '$lib/components/Modal.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import { userPermissions } from '$lib/stores/permissions';
  import {
    formatNumber,
    getApiErrorMessage,
    normalizeMeta,
    type Category,
    type Item,
    type PaginatedResponse
  } from '$lib/inventory';

  type ItemForm = {
    sku: string;
    category_id: string;
    name: string;
    unit: string;
    minimum_stock: number;
  };

  let items: Item[] = [];
  let categories: Category[] = [];
  let loading = true;
  let error = '';
  let search = '';
  let categoryFilter = '';
  let currentPage = 1;
  let lastPage = 1;
  let totalItems = 0;
  let perPage = 25;
  const perPageOptions = [10, 25, 50, 100];

  let showModal = false;
  let editingItem: Item | null = null;
  let form: ItemForm = {
    sku: '',
    category_id: '',
    name: '',
    unit: 'pcs',
    minimum_stock: 0
  };

  let canCreate = false;
  let canUpdate = false;
  let canDelete = false;

  $: {
    const permissions = $userPermissions ?? [];
    canCreate = permissions.includes('item-create');
    canUpdate = permissions.includes('item-update');
    canDelete = permissions.includes('item-delete');
  }

  async function fetchItems() {
    loading = true;
    error = '';

    try {
      const response = await axiosClient.get<PaginatedResponse<Item>>('/items', {
        params: {
          search: search || undefined,
          category_id: categoryFilter || undefined,
          page: currentPage,
          per_page: perPage
        }
      });

      const payload = response.data;
      items = payload.data ?? [];
      const meta = normalizeMeta(payload, items.length);
      currentPage = meta.current_page;
      lastPage = meta.last_page;
      totalItems = meta.total;
    } catch (err) {
      error = getApiErrorMessage(err, 'Gagal memuat item.');
    } finally {
      loading = false;
    }
  }

  async function fetchCategories() {
    try {
      const response = await axiosClient.get<PaginatedResponse<Category>>('/categories', {
        params: { type: 'item', per_page: 100 }
      });
      categories = response.data.data ?? [];
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

  function payloadFromForm() {
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
        await axiosClient.put(`/items/${editingItem.id}`, payload);
        alert('Item berhasil diperbarui.');
      } else {
        await axiosClient.post('/items', payload);
        alert('Item berhasil ditambahkan.');
      }

      showModal = false;
      fetchItems();
    } catch (err) {
      alert(getApiErrorMessage(err, 'Gagal menyimpan item.'));
    }
  }

  async function deleteItem(item: Item) {
    if (!canDelete) return;
    if (!confirm(`Hapus item "${item.name}"?`)) return;

    try {
      await axiosClient.delete(`/items/${item.id}`);
      alert('Item berhasil dihapus.');
      fetchItems();
    } catch (err) {
      alert(getApiErrorMessage(err, 'Gagal menghapus item.'));
    }
  }

  function totalStock(item: Item): number {
    return (item.inventories ?? []).reduce(
      (sum, inventory) => sum + Number(inventory.quantity ?? 0),
      0
    );
  }

  function stockBadgeClasses(item: Item): string {
    const total = totalStock(item);

    if (total <= item.minimum_stock) {
      return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
    }

    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
  }

  onMount(() => {
    fetchCategories();
    fetchItems();
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
        on:click={openCreateModal}
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
        on:input={handleSearchDebounced}
        placeholder="Cari item atau SKU..."
        class="h-10 w-full rounded-md border border-gray-300 bg-white pr-3 pl-10 text-sm text-gray-900 placeholder-gray-500 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      />
    </div>

    <select
      bind:value={categoryFilter}
      on:change={handleFilterChange}
      class="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm font-medium text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Semua kategori</option>
      {#each categories as category (category.id)}
        <option value={category.id}>{category.name}</option>
      {/each}
    </select>
  </div>

  <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-black">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[980px] divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-neutral-900">
          <tr>
            <th
              class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Item
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Kategori
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Unit
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Stok
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Minimum
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
              <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Memuat item...</td
              >
            </tr>
          {:else if error}
            <tr>
              <td colspan="6" class="px-4 py-8 text-center text-sm text-red-600">{error}</td>
            </tr>
          {:else if items.length === 0}
            <tr>
              <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500"
                >Belum ada item.</td
              >
            </tr>
          {:else}
            {#each items as item (item.id)}
              <tr class="hover:bg-gray-50 dark:hover:bg-neutral-950">
                <td class="px-4 py-4">
                  <div class="font-semibold text-gray-900 dark:text-gray-100">{item.name}</div>
                  <div class="mt-1 font-mono text-xs text-gray-500 dark:text-gray-400">
                    {item.sku}
                  </div>
                </td>
                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                  {item.category?.name ?? '-'}
                </td>
                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{item.unit}</td>
                <td class="px-4 py-4 text-right">
                  <span
                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {stockBadgeClasses(
                      item
                    )}"
                  >
                    {formatNumber(totalStock(item))}
                  </span>
                </td>
                <td
                  class="px-4 py-4 text-right text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {formatNumber(item.minimum_stock)}
                </td>
                <td class="px-4 py-4 text-right">
                  <div class="inline-flex items-center gap-2">
                    {#if canUpdate}
                      <button
                        type="button"
                        on:click={() => openEditModal(item)}
                        class="rounded-md p-2 text-blue-600 hover:bg-blue-50 hover:text-blue-800 dark:text-blue-400 dark:hover:bg-blue-950"
                        title="Edit"
                        aria-label="Edit item"
                      >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                          />
                        </svg>
                      </button>
                    {/if}

                    {#if canDelete}
                      <button
                        type="button"
                        on:click={() => deleteItem(item)}
                        class="rounded-md p-2 text-red-600 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-950"
                        title="Hapus"
                        aria-label="Hapus item"
                      >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3 0V5a2 2 0 012-2h0a2 2 0 012 2v2"
                          />
                        </svg>
                      </button>
                    {/if}
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
      onPageChange={(page) => {
        currentPage = page;
        fetchItems();
      }}
      onPerPageChange={(nextPerPage) => {
        perPage = nextPerPage;
        currentPage = 1;
        fetchItems();
      }}
    />
  </div>
</div>

<Modal bind:show={showModal} title={editingItem ? 'Edit Item' : 'Tambah Item'}>
  <form class="space-y-4" on:submit|preventDefault={submitItem}>
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
        on:click={() => (showModal = false)}
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
