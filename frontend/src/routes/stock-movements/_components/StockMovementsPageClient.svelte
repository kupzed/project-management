<script lang="ts">
  import { onMount } from 'svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import Drawer from '$lib/components/Drawer.svelte';
  import {
    createStockMovement,
    updateStockMovement,
    deleteStockMovement,
    fetchItems as fetchItemRecords,
    fetchProjectOptions,
    fetchStockMovements,
    fetchWarehouses as fetchWarehouseRecords
  } from '$lib/services/inventoryService';
  import { userPermissions } from '$lib/stores/permissions';
  import { showError, showSuccess } from '$lib/utils/toast';
  import { extractApiErrors } from '$lib/utils/errors';
  import { formatDateTime } from '$lib/utils/formatters';
  import {
    STOCK_MOVEMENT_TYPE_OPTIONS,
    formatNumber,
    type Item,
    type ProjectOption,
    type StockMovement,
    type StockMovementType,
    type Warehouse
  } from '$lib/inventory';

  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import StockMovementModal from './StockMovementModal.svelte';
  import {
    actionLabel,
    buildMovementPayload,
    emptyMovementForm,
    movementBadgeClasses,
    movementTypeLabel,
    warehouseFlow,
    type MovementAction,
    type MovementForm
  } from './stock-movement';

  let movements = $state<StockMovement[]>([]);
  let items = $state<Item[]>([]);
  let warehouses = $state<Warehouse[]>([]);
  let projects = $state<ProjectOption[]>([]);
  let loading = $state(true);
  let error = $state('');
  let typeFilter = $state<'' | StockMovementType>('');
  let itemFilter = $state('');
  let warehouseFilter = $state('');
  let projectFilter = $state('');
  let currentPage = $state(1);
  let lastPage = $state(1);
  let totalItems = $state(0);
  let perPage = $state(25);
  const perPageOptions = [10, 25, 50, 100];

  let showModal = $state(false);
  let isEditMode = $state(false);
  let editingMovementId = $state<number | null>(null);
  let activeAction = $state<MovementAction>('inbound');
  let form = $state<MovementForm>(emptyMovementForm());

  let showDetailDrawer = $state(false);
  let selectedMovement = $state<StockMovement | null>(null);

  let canCreate = $derived(($userPermissions ?? []).includes('stock-movement-create'));
  let canUpdate = $derived(($userPermissions ?? []).includes('stock-movement-update'));
  let canDelete = $derived(($userPermissions ?? []).includes('stock-movement-delete'));

  function formatForDateTimeLocal(dateStr?: string | null): string {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '';
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
  }

  async function fetchMovements() {
    loading = true;
    error = '';

    try {
      const result = await fetchStockMovements({
        type: typeFilter || undefined,
        item_id: itemFilter || undefined,
        warehouse_id: warehouseFilter || undefined,
        project_id: projectFilter || undefined,
        page: currentPage,
        per_page: perPage
      });

      movements = result.data;
      currentPage = result.meta.current_page;
      lastPage = result.meta.last_page;
      totalItems = result.meta.total;
    } catch (err) {
      error = extractApiErrors(err);
    } finally {
      loading = false;
    }
  }

  async function fetchDependencies() {
    await Promise.all([loadItems(), loadWarehouses(), loadProjects()]);
  }

  async function loadItems() {
    try {
      const result = await fetchItemRecords({ per_page: 100 });
      items = result.data;
    } catch (err) {
      console.error('Gagal memuat item:', err);
    }
  }

  async function loadWarehouses() {
    try {
      const result = await fetchWarehouseRecords({ per_page: 100 });
      warehouses = result.data;
    } catch (err) {
      console.error('Gagal memuat gudang:', err);
    }
  }

  async function loadProjects() {
    try {
      projects = await fetchProjectOptions();
    } catch (err) {
      console.error('Gagal memuat project:', err);
    }
  }

  function handleFilterChange() {
    currentPage = 1;
    fetchMovements();
  }

  function openActionModal(action: MovementAction) {
    if (!canCreate) return;
    isEditMode = false;
    editingMovementId = null;
    activeAction = action;
    form = emptyMovementForm();
    showModal = true;
  }

  function openDetail(movement: StockMovement) {
    selectedMovement = movement;
    showDetailDrawer = true;
  }

  function openEditModal(movement: StockMovement) {
    if (!canUpdate) return;
    isEditMode = true;
    editingMovementId = movement.id;
    activeAction =
      movement.type === 'project_allocation'
        ? 'allocate-project'
        : (movement.type as MovementAction);

    const formattedOccurred = formatForDateTimeLocal(movement.occurred_at);

    form = {
      item_id: String(movement.item_id),
      source_warehouse_id: String(movement.source_warehouse_id ?? ''),
      destination_warehouse_id: String(movement.destination_warehouse_id ?? ''),
      warehouse_id: String(movement.source_warehouse_id ?? ''),
      project_id: String(movement.project_id ?? ''),
      quantity: movement.quantity,
      notes: movement.notes ?? '',
      occurred_at: formattedOccurred,
      allocated_at: formattedOccurred,
      placement: ''
    };
    showModal = true;
  }

  async function submitMovement() {
    try {
      if (isEditMode && editingMovementId !== null) {
        const payload = {
          quantity: Number(form.quantity),
          notes: form.notes || null,
          occurred_at:
            (activeAction === 'allocate-project' ? form.allocated_at : form.occurred_at) || null
        };
        await updateStockMovement(editingMovementId, payload);
        showSuccess('Mutasi stok berhasil diperbarui.');
      } else {
        await createStockMovement(activeAction, buildMovementPayload(form, activeAction));
        showSuccess(`${actionLabel(activeAction)} berhasil dicatat.`);
      }
      showModal = false;
      await fetchMovements();
      await Promise.all([loadItems(), loadWarehouses()]);
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function deleteMovement(movement: StockMovement) {
    if (!canDelete) return;
    const confirmed = await confirm({
      title: `Hapus mutasi stok ini?`,
      text: 'Efek mutasi pada stok gudang akan dibatalkan otomatis.',
      confirmText: 'Hapus',
      isDangerous: true
    });

    if (!confirmed) return;

    try {
      await deleteStockMovement(movement.id);
      showSuccess('Mutasi stok berhasil dihapus.');
      await fetchMovements();
      await Promise.all([loadItems(), loadWarehouses()]);
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  function handleSubmit(event: SubmitEvent) {
    event.preventDefault();
    void submitMovement();
  }

  function handlePageChange(page: number) {
    currentPage = page;
    void fetchMovements();
  }

  function handlePerPageChange(nextPerPage: number) {
    perPage = nextPerPage;
    currentPage = 1;
    void fetchMovements();
  }

  onMount(() => {
    void fetchDependencies();
    void fetchMovements();
  });
</script>

<svelte:head>
  <title>Mutasi Stok - Indogreen</title>
</svelte:head>

<div class="flex flex-col gap-5">
  <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Mutasi Stok</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Ledger stok immutable untuk penerimaan, pengeluaran, transfer, dan alokasi project.
      </p>
    </div>

    {#if canCreate}
      <div class="grid grid-cols-2 gap-2 sm:flex">
        <button
          type="button"
          onclick={() => openActionModal('inbound')}
          class="h-10 rounded-md bg-emerald-600 px-3 text-sm font-semibold text-white hover:bg-emerald-700"
        >
          Inbound
        </button>
        <button
          type="button"
          onclick={() => openActionModal('outbound')}
          class="h-10 rounded-md bg-red-600 px-3 text-sm font-semibold text-white hover:bg-red-700"
        >
          Outbound
        </button>
        <button
          type="button"
          onclick={() => openActionModal('transfer')}
          class="h-10 rounded-md bg-blue-600 px-3 text-sm font-semibold text-white hover:bg-blue-700"
        >
          Transfer
        </button>
        <button
          type="button"
          onclick={() => openActionModal('allocate-project')}
          class="h-10 rounded-md bg-purple-600 px-3 text-sm font-semibold text-white hover:bg-purple-700"
        >
          Alokasi
        </button>
      </div>
    {/if}
  </div>

  <div
    class="grid gap-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm lg:grid-cols-4 dark:border-gray-800 dark:bg-black"
  >
    <select
      bind:value={typeFilter}
      onchange={handleFilterChange}
      class="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Semua tipe</option>
      {#each STOCK_MOVEMENT_TYPE_OPTIONS as option (option.value)}
        <option value={option.value}>{option.label}</option>
      {/each}
    </select>

    <select
      bind:value={itemFilter}
      onchange={handleFilterChange}
      class="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Semua item</option>
      {#each items as item (item.id)}
        <option value={item.id}>{item.name}</option>
      {/each}
    </select>

    <select
      bind:value={warehouseFilter}
      onchange={handleFilterChange}
      class="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Semua gudang</option>
      {#each warehouses as warehouse (warehouse.id)}
        <option value={warehouse.id}>{warehouse.name}</option>
      {/each}
    </select>

    <select
      bind:value={projectFilter}
      onchange={handleFilterChange}
      class="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Semua project</option>
      {#each projects as project (project.id)}
        <option value={project.id}>{project.name}</option>
      {/each}
    </select>
  </div>

  <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-black">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[1120px] divide-y divide-gray-200 dark:divide-gray-800">
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
              Tipe
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Quantity
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Gudang
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Project
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
              <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                Memuat mutasi stok...
              </td>
            </tr>
          {:else if error}
            <tr>
              <td colspan="6" class="px-4 py-8 text-center text-sm text-red-600">{error}</td>
            </tr>
          {:else if movements.length === 0}
            <tr>
              <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                Belum ada mutasi stok.
              </td>
            </tr>
          {:else}
            {#each movements as movement (movement.id)}
              <tr class="hover:bg-gray-50 dark:hover:bg-neutral-950">
                <td class="px-4 py-4">
                  <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {movement.item?.name ?? '-'}
                  </div>
                  <div class="mt-1 font-mono text-xs text-gray-500">
                    {movement.item?.sku ?? '-'}
                  </div>
                </td>
                <td class="px-4 py-4">
                  <span
                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {movementBadgeClasses(
                      movement.type
                    )}"
                  >
                    {movementTypeLabel(movement.type)}
                  </span>
                </td>
                <td class="px-4 py-4 text-right text-sm font-bold text-gray-900 dark:text-gray-100">
                  {formatNumber(movement.quantity)}
                </td>
                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                  {warehouseFlow(movement)}
                </td>
                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                  {movement.project?.name ?? '-'}
                </td>
                <td class="px-4 py-4 text-right">
                  <div class="inline-flex w-full justify-end">
                    <RowActionButtons
                      label={`Mutasi ${movement.id}`}
                      canEdit={canUpdate}
                      {canDelete}
                      onDetail={() => openDetail(movement)}
                      onEdit={() => openEditModal(movement)}
                      onDelete={() => deleteMovement(movement)}
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

<StockMovementModal
  bind:show={showModal}
  bind:form
  action={activeAction}
  isEdit={isEditMode}
  {items}
  {warehouses}
  {projects}
  onSubmit={handleSubmit}
/>

<Drawer
  bind:show={showDetailDrawer}
  title="Detail Mutasi Stok"
  width="max-w-md"
  onClose={() => (showDetailDrawer = false)}
>
  <div class="py-5">
    {#if selectedMovement}
      <div class="space-y-4">
        <div>
          <span class="font-mono text-xs font-semibold text-gray-500 uppercase dark:text-gray-400"
            >WAKTU</span
          >
          <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">
            {formatDateTime(selectedMovement.occurred_at)}
          </p>
        </div>
        <div>
          <span class="font-mono text-xs font-semibold text-gray-500 uppercase dark:text-gray-400"
            >TIPE</span
          >
          <div class="mt-1">
            <span
              class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {movementBadgeClasses(
                selectedMovement.type
              )}"
            >
              {movementTypeLabel(selectedMovement.type)}
            </span>
          </div>
        </div>
        <div>
          <span class="font-mono text-xs font-semibold text-gray-500 uppercase dark:text-gray-400"
            >ITEM</span
          >
          <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">
            {selectedMovement.item?.name ?? '-'}
          </p>
          <p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">
            SKU: {selectedMovement.item?.sku ?? '-'} &middot; Unit: {selectedMovement.item?.unit ??
              '-'}
          </p>
        </div>
        {#if selectedMovement.type === 'inbound'}
          <div>
            <span class="font-mono text-xs font-semibold text-gray-500 uppercase dark:text-gray-400"
              >GUDANG TUJUAN</span
            >
            <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">
              {selectedMovement.destination_warehouse?.name ?? '-'}
            </p>
          </div>
        {:else if selectedMovement.type === 'outbound' || selectedMovement.type === 'project_allocation'}
          <div>
            <span class="font-mono text-xs font-semibold text-gray-500 uppercase dark:text-gray-400"
              >GUDANG ASAL</span
            >
            <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">
              {selectedMovement.source_warehouse?.name ?? '-'}
            </p>
          </div>
        {:else if selectedMovement.type === 'transfer'}
          <div class="grid grid-cols-2 gap-4">
            <div>
              <span
                class="font-mono text-xs font-semibold text-gray-500 uppercase dark:text-gray-400"
                >GUDANG ASAL</span
              >
              <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">
                {selectedMovement.source_warehouse?.name ?? '-'}
              </p>
            </div>
            <div>
              <span
                class="font-mono text-xs font-semibold text-gray-500 uppercase dark:text-gray-400"
                >GUDANG TUJUAN</span
              >
              <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">
                {selectedMovement.destination_warehouse?.name ?? '-'}
              </p>
            </div>
          </div>
        {/if}
        {#if selectedMovement.type === 'project_allocation'}
          <div>
            <span class="font-mono text-xs font-semibold text-gray-500 uppercase dark:text-gray-400"
              >PROJECT</span
            >
            <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">
              {selectedMovement.project?.name ?? '-'}
            </p>
          </div>
        {/if}
        <div>
          <span class="font-mono text-xs font-semibold text-gray-500 uppercase dark:text-gray-400"
            >QUANTITY</span
          >
          <p class="mt-0.5 text-sm font-bold text-gray-900 dark:text-white">
            {formatNumber(selectedMovement.quantity)}
          </p>
        </div>
        <div>
          <span class="font-mono text-xs font-semibold text-gray-500 uppercase dark:text-gray-400"
            >CATATAN</span
          >
          <p
            class="border-gray-150 mt-0.5 rounded border bg-gray-50 p-2 text-sm whitespace-pre-wrap text-gray-700 dark:border-neutral-800 dark:bg-neutral-900 dark:text-gray-300"
          >
            {selectedMovement.notes || '-'}
          </p>
        </div>
      </div>
    {/if}
  </div>
</Drawer>
