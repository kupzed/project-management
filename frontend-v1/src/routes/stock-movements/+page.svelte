<script lang="ts">
  import { onMount } from 'svelte';
  import axiosClient from '$lib/axiosClient';
  import Modal from '$lib/components/Modal.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import { userPermissions } from '$lib/stores/permissions';
  import {
    STOCK_MOVEMENT_TYPE_OPTIONS,
    formatDateTime,
    formatNumber,
    getApiErrorMessage,
    normalizeMeta,
    type Item,
    type PaginatedResponse,
    type ProjectOption,
    type StockMovement,
    type StockMovementType,
    type Warehouse
  } from '$lib/inventory';

  type MovementAction = 'inbound' | 'outbound' | 'transfer' | 'allocate-project';

  type MovementForm = {
    item_id: string;
    source_warehouse_id: string;
    destination_warehouse_id: string;
    warehouse_id: string;
    project_id: string;
    quantity: number;
    notes: string;
    occurred_at: string;
    allocated_at: string;
  };

  let movements: StockMovement[] = [];
  let items: Item[] = [];
  let warehouses: Warehouse[] = [];
  let projects: ProjectOption[] = [];
  let loading = true;
  let error = '';
  let typeFilter: '' | StockMovementType = '';
  let itemFilter = '';
  let warehouseFilter = '';
  let projectFilter = '';
  let currentPage = 1;
  let lastPage = 1;
  let totalItems = 0;
  let perPage = 25;
  const perPageOptions = [10, 25, 50, 100];

  let showModal = false;
  let activeAction: MovementAction = 'inbound';
  let form: MovementForm = emptyForm();

  let canCreate = false;

  $: {
    const permissions = $userPermissions ?? [];
    canCreate = permissions.includes('stock-movement-create');
  }

  async function fetchMovements() {
    loading = true;
    error = '';

    try {
      const response = await axiosClient.get<PaginatedResponse<StockMovement>>('/stock-movements', {
        params: {
          type: typeFilter || undefined,
          item_id: itemFilter || undefined,
          warehouse_id: warehouseFilter || undefined,
          project_id: projectFilter || undefined,
          page: currentPage,
          per_page: perPage
        }
      });

      const payload = response.data;
      movements = payload.data ?? [];
      const meta = normalizeMeta(payload, movements.length);
      currentPage = meta.current_page;
      lastPage = meta.last_page;
      totalItems = meta.total;
    } catch (err) {
      error = getApiErrorMessage(err, 'Gagal memuat mutasi stok.');
    } finally {
      loading = false;
    }
  }

  async function fetchDependencies() {
    await Promise.all([fetchItems(), fetchWarehouses(), fetchProjects()]);
  }

  async function fetchItems() {
    try {
      const response = await axiosClient.get<PaginatedResponse<Item>>('/items', {
        params: { per_page: 100 }
      });
      items = response.data.data ?? [];
    } catch (err) {
      console.error('Gagal memuat item:', err);
    }
  }

  async function fetchWarehouses() {
    try {
      const response = await axiosClient.get<PaginatedResponse<Warehouse>>('/warehouses', {
        params: { per_page: 100 }
      });
      warehouses = response.data.data ?? [];
    } catch (err) {
      console.error('Gagal memuat gudang:', err);
    }
  }

  async function fetchProjects() {
    try {
      const response = await axiosClient.get<PaginatedResponse<ProjectOption>>('/projects', {
        params: { per_page: 100 }
      });
      projects = (response.data.data ?? []).map((project) => ({
        id: project.id,
        name: project.name
      }));
    } catch (err) {
      console.error('Gagal memuat project:', err);
    }
  }

  function handleFilterChange() {
    currentPage = 1;
    fetchMovements();
  }

  function emptyForm(): MovementForm {
    return {
      item_id: '',
      source_warehouse_id: '',
      destination_warehouse_id: '',
      warehouse_id: '',
      project_id: '',
      quantity: 1,
      notes: '',
      occurred_at: '',
      allocated_at: ''
    };
  }

  function openActionModal(action: MovementAction) {
    if (!canCreate) return;
    activeAction = action;
    form = emptyForm();
    showModal = true;
  }

  function actionLabel(action: MovementAction): string {
    switch (action) {
      case 'inbound':
        return 'Inbound';
      case 'outbound':
        return 'Outbound';
      case 'transfer':
        return 'Transfer';
      case 'allocate-project':
        return 'Alokasi Project';
    }
  }

  function movementTypeLabel(type: StockMovementType): string {
    return STOCK_MOVEMENT_TYPE_OPTIONS.find((option) => option.value === type)?.label ?? type;
  }

  function movementBadgeClasses(type: StockMovementType): string {
    switch (type) {
      case 'inbound':
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
      case 'outbound':
        return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
      case 'transfer':
        return 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300';
      case 'project_allocation':
        return 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300';
    }
  }

  function warehouseFlow(movement: StockMovement): string {
    if (movement.type === 'inbound') return movement.destination_warehouse?.name ?? '-';
    if (movement.type === 'outbound') return movement.source_warehouse?.name ?? '-';
    if (movement.type === 'project_allocation') return movement.source_warehouse?.name ?? '-';

    return `${movement.source_warehouse?.name ?? '-'} -> ${movement.destination_warehouse?.name ?? '-'}`;
  }

  function actionEndpoint(): string {
    return `/stock-movements/${activeAction}`;
  }

  function actionPayload(): Record<string, unknown> {
    const base = {
      item_id: Number(form.item_id),
      quantity: Number(form.quantity),
      notes: form.notes || undefined
    };

    if (activeAction === 'inbound') {
      return {
        ...base,
        destination_warehouse_id: Number(form.destination_warehouse_id),
        occurred_at: form.occurred_at || undefined
      };
    }

    if (activeAction === 'outbound') {
      return {
        ...base,
        source_warehouse_id: Number(form.source_warehouse_id),
        occurred_at: form.occurred_at || undefined
      };
    }

    if (activeAction === 'transfer') {
      return {
        ...base,
        source_warehouse_id: Number(form.source_warehouse_id),
        destination_warehouse_id: Number(form.destination_warehouse_id),
        occurred_at: form.occurred_at || undefined
      };
    }

    return {
      ...base,
      project_id: Number(form.project_id),
      warehouse_id: Number(form.warehouse_id),
      allocated_at: form.allocated_at || undefined
    };
  }

  async function submitMovement() {
    try {
      await axiosClient.post(actionEndpoint(), actionPayload());
      alert(`${actionLabel(activeAction)} berhasil dicatat.`);
      showModal = false;
      fetchMovements();
      fetchItems();
      fetchWarehouses();
    } catch (err) {
      alert(getApiErrorMessage(err, 'Gagal mencatat mutasi stok.'));
    }
  }

  onMount(() => {
    fetchDependencies();
    fetchMovements();
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
          on:click={() => openActionModal('inbound')}
          class="h-10 rounded-md bg-emerald-600 px-3 text-sm font-semibold text-white hover:bg-emerald-700"
        >
          Inbound
        </button>
        <button
          type="button"
          on:click={() => openActionModal('outbound')}
          class="h-10 rounded-md bg-red-600 px-3 text-sm font-semibold text-white hover:bg-red-700"
        >
          Outbound
        </button>
        <button
          type="button"
          on:click={() => openActionModal('transfer')}
          class="h-10 rounded-md bg-blue-600 px-3 text-sm font-semibold text-white hover:bg-blue-700"
        >
          Transfer
        </button>
        <button
          type="button"
          on:click={() => openActionModal('allocate-project')}
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
      on:change={handleFilterChange}
      class="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Semua tipe</option>
      {#each STOCK_MOVEMENT_TYPE_OPTIONS as option (option.value)}
        <option value={option.value}>{option.label}</option>
      {/each}
    </select>

    <select
      bind:value={itemFilter}
      on:change={handleFilterChange}
      class="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Semua item</option>
      {#each items as item (item.id)}
        <option value={item.id}>{item.name}</option>
      {/each}
    </select>

    <select
      bind:value={warehouseFilter}
      on:change={handleFilterChange}
      class="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
    >
      <option value="">Semua gudang</option>
      {#each warehouses as warehouse (warehouse.id)}
        <option value={warehouse.id}>{warehouse.name}</option>
      {/each}
    </select>

    <select
      bind:value={projectFilter}
      on:change={handleFilterChange}
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
              Waktu
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Tipe
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Item
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
              Quantity
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
            >
              Catatan
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          {#if loading}
            <tr>
              <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                Memuat mutasi stok...
              </td>
            </tr>
          {:else if error}
            <tr>
              <td colspan="7" class="px-4 py-8 text-center text-sm text-red-600">{error}</td>
            </tr>
          {:else if movements.length === 0}
            <tr>
              <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                Belum ada mutasi stok.
              </td>
            </tr>
          {:else}
            {#each movements as movement (movement.id)}
              <tr class="hover:bg-gray-50 dark:hover:bg-neutral-950">
                <td class="px-4 py-4 text-sm whitespace-nowrap text-gray-600 dark:text-gray-300">
                  {formatDateTime(movement.occurred_at)}
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
                <td class="px-4 py-4">
                  <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {movement.item?.name ?? '-'}
                  </div>
                  <div class="mt-1 font-mono text-xs text-gray-500">
                    {movement.item?.sku ?? '-'}
                  </div>
                </td>
                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                  {warehouseFlow(movement)}
                </td>
                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                  {movement.project?.name ?? '-'}
                </td>
                <td class="px-4 py-4 text-right text-sm font-bold text-gray-900 dark:text-gray-100">
                  {formatNumber(movement.quantity)}
                </td>
                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                  {movement.notes || '-'}
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
        fetchMovements();
      }}
      onPerPageChange={(nextPerPage) => {
        perPage = nextPerPage;
        currentPage = 1;
        fetchMovements();
      }}
    />
  </div>
</div>

<Modal bind:show={showModal} title={`Catat ${actionLabel(activeAction)}`} maxWidth="max-w-2xl">
  <form class="space-y-4" on:submit|preventDefault={submitMovement}>
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label
          for="movement-item"
          class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Item
        </label>
        <select
          id="movement-item"
          bind:value={form.item_id}
          required
          class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        >
          <option value="" disabled>Pilih item</option>
          {#each items as item (item.id)}
            <option value={item.id}>{item.name} · {item.sku}</option>
          {/each}
        </select>
      </div>

      <div>
        <label
          for="movement-quantity"
          class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Quantity
        </label>
        <input
          id="movement-quantity"
          type="number"
          min="1"
          bind:value={form.quantity}
          required
          class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        />
      </div>
    </div>

    {#if activeAction === 'inbound'}
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label
            for="movement-destination"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Gudang Tujuan
          </label>
          <select
            id="movement-destination"
            bind:value={form.destination_warehouse_id}
            required
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          >
            <option value="" disabled>Pilih gudang</option>
            {#each warehouses as warehouse (warehouse.id)}
              <option value={warehouse.id}>{warehouse.name}</option>
            {/each}
          </select>
        </div>
        <div>
          <label
            for="movement-date"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Waktu
          </label>
          <input
            id="movement-date"
            type="datetime-local"
            bind:value={form.occurred_at}
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          />
        </div>
      </div>
    {:else if activeAction === 'outbound'}
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label
            for="movement-source"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Gudang Asal
          </label>
          <select
            id="movement-source"
            bind:value={form.source_warehouse_id}
            required
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          >
            <option value="" disabled>Pilih gudang</option>
            {#each warehouses as warehouse (warehouse.id)}
              <option value={warehouse.id}>{warehouse.name}</option>
            {/each}
          </select>
        </div>
        <div>
          <label
            for="movement-date"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Waktu
          </label>
          <input
            id="movement-date"
            type="datetime-local"
            bind:value={form.occurred_at}
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          />
        </div>
      </div>
    {:else if activeAction === 'transfer'}
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label
            for="movement-source"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Gudang Asal
          </label>
          <select
            id="movement-source"
            bind:value={form.source_warehouse_id}
            required
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          >
            <option value="" disabled>Pilih gudang</option>
            {#each warehouses as warehouse (warehouse.id)}
              <option value={warehouse.id}>{warehouse.name}</option>
            {/each}
          </select>
        </div>
        <div>
          <label
            for="movement-destination"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Gudang Tujuan
          </label>
          <select
            id="movement-destination"
            bind:value={form.destination_warehouse_id}
            required
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          >
            <option value="" disabled>Pilih gudang</option>
            {#each warehouses as warehouse (warehouse.id)}
              <option value={warehouse.id}>{warehouse.name}</option>
            {/each}
          </select>
        </div>
      </div>

      <div>
        <label
          for="movement-date"
          class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Waktu
        </label>
        <input
          id="movement-date"
          type="datetime-local"
          bind:value={form.occurred_at}
          class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        />
      </div>
    {:else}
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label
            for="movement-project"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Project
          </label>
          <select
            id="movement-project"
            bind:value={form.project_id}
            required
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          >
            <option value="" disabled>Pilih project</option>
            {#each projects as project (project.id)}
              <option value={project.id}>{project.name}</option>
            {/each}
          </select>
        </div>
        <div>
          <label
            for="movement-warehouse"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Gudang
          </label>
          <select
            id="movement-warehouse"
            bind:value={form.warehouse_id}
            required
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          >
            <option value="" disabled>Pilih gudang</option>
            {#each warehouses as warehouse (warehouse.id)}
              <option value={warehouse.id}>{warehouse.name}</option>
            {/each}
          </select>
        </div>
      </div>

      <div>
        <label
          for="movement-allocated-date"
          class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Waktu Alokasi
        </label>
        <input
          id="movement-allocated-date"
          type="datetime-local"
          bind:value={form.allocated_at}
          class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        />
      </div>
    {/if}

    <div>
      <label
        for="movement-notes"
        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        Catatan
      </label>
      <textarea
        id="movement-notes"
        bind:value={form.notes}
        rows="3"
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
      ></textarea>
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
        Catat
      </button>
    </div>
  </form>
</Modal>
