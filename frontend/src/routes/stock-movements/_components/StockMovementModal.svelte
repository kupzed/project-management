<script lang="ts">
  import Modal from '$lib/components/Modal.svelte';
  import type { Item, ProjectOption, Warehouse } from '$lib/inventory';
  import { actionLabel, type MovementAction, type MovementForm } from './stock-movement';

  /** Bindable stock movement form modal for every movement action. */
  let {
    show = $bindable(false),
    action,
    form = $bindable(),
    items,
    warehouses,
    projects,
    onSubmit
  }: {
    show?: boolean;
    action: MovementAction;
    form: MovementForm;
    items: Item[];
    warehouses: Warehouse[];
    projects: ProjectOption[];
    onSubmit: (event: SubmitEvent) => void;
  } = $props();
</script>

<Modal bind:show title={`Catat ${actionLabel(action)}`} maxWidth="max-w-2xl">
  <form class="space-y-4" onsubmit={onSubmit}>
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label
          for="movement-item"
          class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Item</label
        >
        <select
          id="movement-item"
          bind:value={form.item_id}
          required
          class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        >
          <option value="" disabled>Pilih item</option>
          {#each items as item (item.id)}
            <option value={item.id}>{item.name} &middot; {item.sku}</option>
          {/each}
        </select>
      </div>
      <div>
        <label
          for="movement-quantity"
          class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label
        >
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

    {#if action === 'inbound'}
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label
            for="movement-destination"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
            >Gudang Tujuan</label
          >
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
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Waktu</label
          >
          <input
            id="movement-date"
            type="datetime-local"
            bind:value={form.occurred_at}
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          />
        </div>
      </div>
    {:else if action === 'outbound'}
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label
            for="movement-source"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
            >Gudang Asal</label
          >
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
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Waktu</label
          >
          <input
            id="movement-date"
            type="datetime-local"
            bind:value={form.occurred_at}
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          />
        </div>
      </div>
    {:else if action === 'transfer'}
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label
            for="movement-source"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
            >Gudang Asal</label
          >
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
            >Gudang Tujuan</label
          >
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
          class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Waktu</label
        >
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
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Project</label
          >
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
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Gudang</label
          >
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
          >Waktu Alokasi</label
        >
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
        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan</label
      >
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
        onclick={() => (show = false)}
        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-neutral-900"
        >Batal</button
      >
      <button
        type="submit"
        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
        >Catat</button
      >
    </div>
  </form>
</Modal>
