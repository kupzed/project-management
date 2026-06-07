<script lang="ts">
  import { onMount } from 'svelte';
  import Drawer from '$lib/components/Drawer.svelte';
  import Modal from '$lib/components/Modal.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import {
    createWarehouse,
    deleteWarehouse as deleteWarehouseRecord,
    fetchWarehouse,
    fetchWarehouses as fetchWarehouseRecords,
    updateWarehouse,
    type WarehouseForm
  } from '$lib/services/inventoryService';
  import { userPermissions } from '$lib/stores/permissions';
  import { showError, showSuccess } from '$lib/utils/toast';
  import { extractApiErrors } from '$lib/utils/errors';
  import { formatNumber, type Warehouse } from '$lib/inventory';
  import WarehouseTable from './WarehouseTable.svelte';

  let warehouses = $state<Warehouse[]>([]);
  let selectedWarehouse = $state<Warehouse | null>(null);
  let loading = $state(true);
  let detailLoading = $state(false);
  let error = $state('');
  let search = $state('');
  let currentPage = $state(1);
  let lastPage = $state(1);
  let totalItems = $state(0);
  let perPage = $state(25);
  const perPageOptions = [10, 25, 50, 100];

  let showModal = $state(false);
  let showDetailDrawer = $state(false);
  let editingWarehouse = $state<Warehouse | null>(null);
  let form = $state<WarehouseForm>({
    name: '',
    location: ''
  });

  let canCreate = $derived(($userPermissions ?? []).includes('warehouse-create'));
  let canUpdate = $derived(($userPermissions ?? []).includes('warehouse-update'));
  let canDelete = $derived(($userPermissions ?? []).includes('warehouse-delete'));

  async function fetchWarehouses() {
    loading = true;
    error = '';

    try {
      const result = await fetchWarehouseRecords({
        search: search || undefined,
        page: currentPage,
        per_page: perPage
      });

      warehouses = result.data;
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
      fetchWarehouses();
    }, 500);
  }

  function openCreateModal() {
    if (!canCreate) return;
    editingWarehouse = null;
    form = { name: '', location: '' };
    showModal = true;
  }

  function openEditModal(warehouse: Warehouse) {
    if (!canUpdate) return;
    editingWarehouse = warehouse;
    form = {
      name: warehouse.name,
      location: warehouse.location ?? ''
    };
    showModal = true;
  }

  async function submitWarehouse() {
    try {
      if (editingWarehouse) {
        await updateWarehouse(editingWarehouse.id, form);
        showSuccess('Gudang berhasil diperbarui.');
      } else {
        await createWarehouse(form);
        showSuccess('Gudang berhasil ditambahkan.');
      }

      showModal = false;
      await fetchWarehouses();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function deleteWarehouse(warehouse: Warehouse) {
    if (!canDelete) return;
    const confirmed = await confirm({
      title: `Hapus gudang "${warehouse.name}"?`,
      text: 'Gudang yang dihapus tidak dapat dikembalikan.',
      confirmText: 'Hapus',
      isDangerous: true
    });

    if (!confirmed) return;

    try {
      await deleteWarehouseRecord(warehouse.id);
      showSuccess('Gudang berhasil dihapus.');
      await fetchWarehouses();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function openDetailDrawer(warehouse: Warehouse) {
    selectedWarehouse = warehouse;
    showDetailDrawer = true;
    detailLoading = true;

    try {
      selectedWarehouse = await fetchWarehouse(warehouse.id);
    } catch (err) {
      showError(extractApiErrors(err));
    } finally {
      detailLoading = false;
    }
  }

  function closeDetailDrawer() {
    showDetailDrawer = false;
  }

  function handleSubmit(event: SubmitEvent) {
    event.preventDefault();
    void submitWarehouse();
  }

  function handlePageChange(page: number) {
    currentPage = page;
    void fetchWarehouses();
  }

  function handlePerPageChange(nextPerPage: number) {
    perPage = nextPerPage;
    currentPage = 1;
    void fetchWarehouses();
  }

  onMount(fetchWarehouses);
</script>

<svelte:head>
  <title>Gudang - Indogreen</title>
</svelte:head>

<div class="flex flex-col gap-5">
  <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Gudang</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Kelola lokasi penyimpanan dan pantau ringkasan stok per gudang.
      </p>
    </div>

    {#if canCreate}
      <button
        type="button"
        onclick={openCreateModal}
        class="inline-flex h-10 items-center justify-center rounded-md bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
      >
        Tambah Gudang
      </button>
    {/if}
  </div>

  <div
    class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-black"
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
        placeholder="Cari nama atau lokasi gudang..."
        class="h-10 w-full rounded-md border border-gray-300 bg-white pr-3 pl-10 text-sm text-gray-900 placeholder-gray-500 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      />
    </div>
  </div>

  <WarehouseTable
    {warehouses}
    {loading}
    {error}
    {currentPage}
    {lastPage}
    {totalItems}
    {perPage}
    {perPageOptions}
    {canUpdate}
    {canDelete}
    onDetail={openDetailDrawer}
    onEdit={openEditModal}
    onDelete={deleteWarehouse}
    onPageChange={handlePageChange}
    onPerPageChange={handlePerPageChange}
  />
</div>

<Modal bind:show={showModal} title={editingWarehouse ? 'Edit Gudang' : 'Tambah Gudang'}>
  <form class="space-y-4" onsubmit={handleSubmit}>
    <div>
      <label
        for="warehouse-name"
        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        Nama Gudang
      </label>
      <input
        id="warehouse-name"
        type="text"
        bind:value={form.name}
        required
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      />
    </div>

    <div>
      <label
        for="warehouse-location"
        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        Lokasi
      </label>
      <textarea
        id="warehouse-location"
        bind:value={form.location}
        rows="3"
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      ></textarea>
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

<Drawer
  bind:show={showDetailDrawer}
  title="Detail Gudang"
  width="max-w-2xl"
  onClose={closeDetailDrawer}
>
  <div class="py-5">
    {#if detailLoading}
      <p class="text-sm text-gray-500">Memuat detail gudang...</p>
    {:else if selectedWarehouse}
      <div class="space-y-5">
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">{selectedWarehouse.name}</h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {selectedWarehouse.location || 'Tidak ada lokasi tercatat.'}
          </p>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
          <div
            class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-neutral-900"
          >
            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Stok Tersimpan</h3>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] divide-y divide-gray-100 dark:divide-gray-800">
              <thead class="bg-white dark:bg-black">
                <tr>
                  <th
                    class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase"
                  >
                    Item
                  </th>
                  <th
                    class="px-4 py-3 text-right text-xs font-bold tracking-wide text-gray-500 uppercase"
                  >
                    Quantity
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                {#if !selectedWarehouse.inventories || selectedWarehouse.inventories.length === 0}
                  <tr>
                    <td colspan="2" class="px-4 py-6 text-center text-sm text-gray-500">
                      Belum ada stok di gudang ini.
                    </td>
                  </tr>
                {:else}
                  {#each selectedWarehouse.inventories as inventory (inventory.id)}
                    <tr>
                      <td class="px-4 py-3">
                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                          {inventory.item?.name ?? '-'}
                        </div>
                        <div class="mt-1 font-mono text-xs text-gray-500">
                          {inventory.item?.sku ?? '-'} · {inventory.item?.unit ?? '-'}
                        </div>
                      </td>
                      <td
                        class="px-4 py-3 text-right text-sm font-bold text-gray-900 dark:text-gray-100"
                      >
                        {formatNumber(inventory.quantity)}
                      </td>
                    </tr>
                  {/each}
                {/if}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    {/if}
  </div>
</Drawer>
