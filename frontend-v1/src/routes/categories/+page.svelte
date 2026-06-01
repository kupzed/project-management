<script lang="ts">
	import { onMount } from 'svelte';
	import axiosClient from '$lib/axiosClient';
	import Modal from '$lib/components/Modal.svelte';
	import Pagination from '$lib/components/Pagination.svelte';
	import { userPermissions } from '$lib/stores/permissions';
	import {
		CATEGORY_TYPE_OPTIONS,
		getApiErrorMessage,
		normalizeMeta,
		type Category,
		type CategoryType,
		type PaginatedResponse
	} from '$lib/inventory';

	let categories: Category[] = [];
	let loading = true;
	let error = '';
	let search = '';
	let typeFilter: '' | CategoryType = '';
	let currentPage = 1;
	let lastPage = 1;
	let totalItems = 0;
	let perPage = 25;
	const perPageOptions = [10, 25, 50, 100];

	let showModal = false;
	let editingCategory: Category | null = null;
	let form: { name: string; type: CategoryType } = {
		name: '',
		type: 'item'
	};

	let canCreate = false;
	let canUpdate = false;
	let canDelete = false;

	$: {
		const permissions = $userPermissions ?? [];
		canCreate = permissions.includes('category-create');
		canUpdate = permissions.includes('category-update');
		canDelete = permissions.includes('category-delete');
	}

	async function fetchCategories() {
		loading = true;
		error = '';

		try {
			const response = await axiosClient.get<PaginatedResponse<Category>>('/categories', {
				params: {
					search: search || undefined,
					type: typeFilter || undefined,
					page: currentPage,
					per_page: perPage
				}
			});

			const payload = response.data;
			categories = payload.data ?? [];
			const meta = normalizeMeta(payload, categories.length);
			currentPage = meta.current_page;
			lastPage = meta.last_page;
			totalItems = meta.total;
		} catch (err) {
			error = getApiErrorMessage(err, 'Gagal memuat kategori.');
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
				await axiosClient.put(`/categories/${editingCategory.id}`, form);
				alert('Kategori berhasil diperbarui.');
			} else {
				await axiosClient.post('/categories', form);
				alert('Kategori berhasil ditambahkan.');
			}

			showModal = false;
			fetchCategories();
		} catch (err) {
			alert(getApiErrorMessage(err, 'Gagal menyimpan kategori.'));
		}
	}

	async function deleteCategory(category: Category) {
		if (!canDelete) return;
		if (!confirm(`Hapus kategori "${category.name}"?`)) return;

		try {
			await axiosClient.delete(`/categories/${category.id}`);
			alert('Kategori berhasil dihapus.');
			fetchCategories();
		} catch (err) {
			alert(getApiErrorMessage(err, 'Gagal menghapus kategori.'));
		}
	}

	function typeLabel(type: CategoryType): string {
		return CATEGORY_TYPE_OPTIONS.find((option) => option.value === type)?.label ?? type;
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
				on:click={openCreateModal}
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
				on:input={handleSearchDebounced}
				placeholder="Cari nama kategori..."
				class="h-10 w-full rounded-md border border-gray-300 bg-white pr-3 pl-10 text-sm text-gray-900 placeholder-gray-500 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
			/>
		</div>

		<select
			bind:value={typeFilter}
			on:change={handleFilterChange}
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
									<div class="inline-flex items-center gap-2">
										{#if canUpdate}
											<button
												type="button"
												on:click={() => openEditModal(category)}
												class="rounded-md p-2 text-blue-600 hover:bg-blue-50 hover:text-blue-800 dark:text-blue-400 dark:hover:bg-blue-950"
												title="Edit"
												aria-label="Edit kategori"
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
												on:click={() => deleteCategory(category)}
												class="rounded-md p-2 text-red-600 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-950"
												title="Hapus"
												aria-label="Hapus kategori"
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
				fetchCategories();
			}}
			onPerPageChange={(nextPerPage) => {
				perPage = nextPerPage;
				currentPage = 1;
				fetchCategories();
			}}
		/>
	</div>
</div>

<Modal bind:show={showModal} title={editingCategory ? 'Edit Kategori' : 'Tambah Kategori'}>
	<form class="space-y-4" on:submit|preventDefault={submitCategory}>
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
