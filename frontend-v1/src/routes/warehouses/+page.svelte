<script lang="ts">
	import { onMount } from 'svelte';
	import axiosClient from '$lib/axiosClient';
	import Drawer from '$lib/components/Drawer.svelte';
	import Modal from '$lib/components/Modal.svelte';
	import Pagination from '$lib/components/Pagination.svelte';
	import { userPermissions } from '$lib/stores/permissions';
	import {
		formatNumber,
		getApiErrorMessage,
		normalizeMeta,
		type PaginatedResponse,
		type Warehouse
	} from '$lib/inventory';

	type WarehouseForm = {
		name: string;
		location: string;
	};

	let warehouses: Warehouse[] = [];
	let selectedWarehouse: Warehouse | null = null;
	let loading = true;
	let detailLoading = false;
	let error = '';
	let search = '';
	let currentPage = 1;
	let lastPage = 1;
	let totalItems = 0;
	let perPage = 25;
	const perPageOptions = [10, 25, 50, 100];

	let showModal = false;
	let showDetailDrawer = false;
	let editingWarehouse: Warehouse | null = null;
	let form: WarehouseForm = {
		name: '',
		location: ''
	};

	let canCreate = false;
	let canUpdate = false;
	let canDelete = false;

	$: {
		const permissions = $userPermissions ?? [];
		canCreate = permissions.includes('warehouse-create');
		canUpdate = permissions.includes('warehouse-update');
		canDelete = permissions.includes('warehouse-delete');
	}

	async function fetchWarehouses() {
		loading = true;
		error = '';

		try {
			const response = await axiosClient.get<PaginatedResponse<Warehouse>>('/warehouses', {
				params: {
					search: search || undefined,
					page: currentPage,
					per_page: perPage
				}
			});

			const payload = response.data;
			warehouses = payload.data ?? [];
			const meta = normalizeMeta(payload, warehouses.length);
			currentPage = meta.current_page;
			lastPage = meta.last_page;
			totalItems = meta.total;
		} catch (err) {
			error = getApiErrorMessage(err, 'Gagal memuat gudang.');
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
				await axiosClient.put(`/warehouses/${editingWarehouse.id}`, form);
				alert('Gudang berhasil diperbarui.');
			} else {
				await axiosClient.post('/warehouses', form);
				alert('Gudang berhasil ditambahkan.');
			}

			showModal = false;
			fetchWarehouses();
		} catch (err) {
			alert(getApiErrorMessage(err, 'Gagal menyimpan gudang.'));
		}
	}

	async function deleteWarehouse(warehouse: Warehouse) {
		if (!canDelete) return;
		if (!confirm(`Hapus gudang "${warehouse.name}"?`)) return;

		try {
			await axiosClient.delete(`/warehouses/${warehouse.id}`);
			alert('Gudang berhasil dihapus.');
			fetchWarehouses();
		} catch (err) {
			alert(getApiErrorMessage(err, 'Gagal menghapus gudang.'));
		}
	}

	async function openDetailDrawer(warehouse: Warehouse) {
		selectedWarehouse = warehouse;
		showDetailDrawer = true;
		detailLoading = true;

		try {
			const response = await axiosClient.get<{ data: Warehouse }>(`/warehouses/${warehouse.id}`);
			selectedWarehouse = response.data.data;
		} catch (err) {
			alert(getApiErrorMessage(err, 'Gagal memuat detail gudang.'));
		} finally {
			detailLoading = false;
		}
	}

	function closeDetailDrawer() {
		showDetailDrawer = false;
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
				on:click={openCreateModal}
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
				on:input={handleSearchDebounced}
				placeholder="Cari nama atau lokasi gudang..."
				class="h-10 w-full rounded-md border border-gray-300 bg-white pr-3 pl-10 text-sm text-gray-900 placeholder-gray-500 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
			/>
		</div>
	</div>

	<div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-black">
		<div class="overflow-x-auto">
			<table class="w-full min-w-[860px] divide-y divide-gray-200 dark:divide-gray-800">
				<thead class="bg-gray-50 dark:bg-neutral-900">
					<tr>
						<th
							class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
						>
							Gudang
						</th>
						<th
							class="px-4 py-3 text-left text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
						>
							Lokasi
						</th>
						<th
							class="px-4 py-3 text-right text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-300"
						>
							Item Stok
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
							<td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500"
								>Memuat gudang...</td
							>
						</tr>
					{:else if error}
						<tr>
							<td colspan="4" class="px-4 py-8 text-center text-sm text-red-600">{error}</td>
						</tr>
					{:else if warehouses.length === 0}
						<tr>
							<td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500"
								>Belum ada gudang.</td
							>
						</tr>
					{:else}
						{#each warehouses as warehouse (warehouse.id)}
							<tr class="hover:bg-gray-50 dark:hover:bg-neutral-950">
								<td class="px-4 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
									{warehouse.name}
								</td>
								<td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
									{warehouse.location || '-'}
								</td>
								<td
									class="px-4 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-200"
								>
									{formatNumber(warehouse.inventories_count ?? 0)}
								</td>
								<td class="px-4 py-4 text-right">
									<div class="inline-flex items-center gap-2">
										<button
											type="button"
											on:click={() => openDetailDrawer(warehouse)}
											class="rounded-md p-2 text-yellow-600 hover:bg-yellow-50 hover:text-yellow-800 dark:text-yellow-400 dark:hover:bg-yellow-950"
											title="Detail"
											aria-label="Detail gudang"
										>
											<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
												/>
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
												/>
											</svg>
										</button>

										{#if canUpdate}
											<button
												type="button"
												on:click={() => openEditModal(warehouse)}
												class="rounded-md p-2 text-blue-600 hover:bg-blue-50 hover:text-blue-800 dark:text-blue-400 dark:hover:bg-blue-950"
												title="Edit"
												aria-label="Edit gudang"
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
												on:click={() => deleteWarehouse(warehouse)}
												class="rounded-md p-2 text-red-600 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-950"
												title="Hapus"
												aria-label="Hapus gudang"
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
				fetchWarehouses();
			}}
			onPerPageChange={(nextPerPage) => {
				perPage = nextPerPage;
				currentPage = 1;
				fetchWarehouses();
			}}
		/>
	</div>
</div>

<Modal bind:show={showModal} title={editingWarehouse ? 'Edit Gudang' : 'Tambah Gudang'}>
	<form class="space-y-4" on:submit|preventDefault={submitWarehouse}>
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

<Drawer
	bind:show={showDetailDrawer}
	title="Detail Gudang"
	width="max-w-2xl"
	on:close={closeDetailDrawer}
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
