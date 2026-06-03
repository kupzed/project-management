<script lang="ts">
	import { formatDate } from '$lib/utils/formatters';
	import type { SortOrder } from '$lib/types';

	/**
	 * Props for date range filtering and created/date sorting.
	 * Bind `dateFrom`, `dateTo`, `sortBy`, and `sortDir` to parent filter state.
	 */
	let {
		title,
		dateFrom = $bindable(''),
		dateTo = $bindable(''),
		sortBy = $bindable('created'),
		sortDir = $bindable('desc' as SortOrder),
		sortByField,
		sortByCreatedLabel,
		sortByDateLabel = title,
		fromLabel = 'Dari Tanggal',
		toLabel = 'Sampai Tanggal',
		clearText = 'Clear All',
		closeText = 'Tutup',
		newestText = 'Terbaru',
		oldestText = 'Terlama',
		newestFirstText = 'Terbaru dulu',
		oldestFirstText = 'Terlama dulu',
		activeLabel = 'Filter',
		fromPrefix = 'Dari',
		toPrefix = 'Sampai',
		ariaLabel = title,
		idPrefix = 'date-filter',
		onFilter,
		onClear
	}: {
		title: string;
		dateFrom?: string;
		dateTo?: string;
		sortBy?: string;
		sortDir?: SortOrder;
		sortByField: string;
		sortByCreatedLabel: string;
		sortByDateLabel?: string;
		fromLabel?: string;
		toLabel?: string;
		clearText?: string;
		closeText?: string;
		newestText?: string;
		oldestText?: string;
		newestFirstText?: string;
		oldestFirstText?: string;
		activeLabel?: string;
		fromPrefix?: string;
		toPrefix?: string;
		ariaLabel?: string;
		idPrefix?: string;
		onFilter?: () => void;
		onClear?: () => void;
	} = $props();

	let open = $state(false);
	let root: HTMLDivElement | undefined = $state();
	let hasActiveDateFilter = $derived(Boolean(dateFrom || dateTo));
	let activeSummary = $derived.by(() => {
		if (dateFrom && dateTo) {
			return `${activeLabel}: ${formatDate(dateFrom)} - ${formatDate(dateTo)}`;
		}

		if (dateFrom) {
			return `${fromPrefix}: ${formatDate(dateFrom)}`;
		}

		if (dateTo) {
			return `${toPrefix}: ${formatDate(dateTo)}`;
		}

		return '';
	});

	function toggleDropdown(): void {
		open = !open;
	}

	function closeDropdown(): void {
		open = false;
	}

	function applyCreatedSort(): void {
		sortBy = 'created';
		onFilter?.();
	}

	function applyDateSort(nextSortDir: SortOrder): void {
		sortBy = sortByField;
		sortDir = nextSortDir;
		onFilter?.();
	}

	function applyFilter(): void {
		onFilter?.();
	}

	function clearFilter(): void {
		dateFrom = '';
		dateTo = '';
		sortBy = 'created';
		sortDir = 'desc';
		onClear?.();
	}

	function handleWindowClick(event: MouseEvent): void {
		if (!open || !root || event.target instanceof Node === false) {
			return;
		}

		if (!root.contains(event.target)) {
			open = false;
		}
	}

	function handleWindowKeydown(event: KeyboardEvent): void {
		if (event.key === 'Escape') {
			open = false;
		}
	}
</script>

<svelte:window onclick={handleWindowClick} onkeydown={handleWindowKeydown} />

<div class="relative" bind:this={root}>
	<button
		type="button"
		onclick={toggleDropdown}
		class="date-filter-button flex items-center space-x-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm
             font-semibold text-gray-900 transition-colors hover:bg-gray-50
             focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-gray-700
             dark:bg-neutral-900 dark:text-gray-100 dark:hover:bg-neutral-800 dark:focus:ring-offset-2 dark:focus:ring-offset-gray-800"
		class:bg-indigo-50={hasActiveDateFilter}
		class:border-indigo-300={hasActiveDateFilter}
		class:text-indigo-700={hasActiveDateFilter}
		aria-expanded={open}
		aria-label={ariaLabel}
	>
		<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
			<path
				stroke-linecap="round"
				stroke-linejoin="round"
				stroke-width="2"
				d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
			></path>
		</svg>
		<span>{title}</span>
		{#if hasActiveDateFilter}
			<div class="h-2 w-2 rounded-full bg-indigo-500"></div>
		{/if}
		<svg
			class="h-4 w-4 transition-transform"
			class:rotate-180={open}
			fill="none"
			stroke="currentColor"
			viewBox="0 0 24 24"
			aria-hidden="true"
		>
			<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"
			></path>
		</svg>
	</button>

	{#if open}
		<div
			class="date-filter-dropdown absolute right-0 z-10 mt-2 w-80 rounded-md border border-gray-300 bg-white p-4 shadow-lg
               dark:border-gray-700 dark:bg-neutral-900"
		>
			<div class="space-y-3">
				<div>
					<span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
						{sortByCreatedLabel}
					</span>
					<select
						bind:value={sortDir}
						onchange={applyCreatedSort}
						class="mb-2 w-full rounded-md border border-gray-300 bg-white px-3
                    py-2 text-sm font-semibold text-gray-900
                    dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
						title={sortByCreatedLabel}
					>
						<option value="desc">{newestText}</option>
						<option value="asc">{oldestText}</option>
					</select>

					<span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
						{sortByDateLabel}
					</span>
					<div
						class="inline-flex w-full overflow-hidden rounded-md border border-gray-300 dark:border-gray-700"
						role="tablist"
						aria-label={sortByDateLabel}
					>
						<button
							type="button"
							onclick={() => applyDateSort('desc')}
							class="w-full px-3 py-1.5 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
							class:bg-indigo-600={sortBy === sortByField && sortDir === 'desc'}
							class:text-white={sortBy === sortByField && sortDir === 'desc'}
							class:bg-white={!(sortBy === sortByField && sortDir === 'desc')}
							class:text-gray-900={!(sortBy === sortByField && sortDir === 'desc')}
							class:dark:bg-neutral-900={!(sortBy === sortByField && sortDir === 'desc')}
							class:dark:text-gray-100={!(sortBy === sortByField && sortDir === 'desc')}
							aria-selected={sortBy === sortByField && sortDir === 'desc'}
							role="tab"
						>
							{newestFirstText}
						</button>
						<button
							type="button"
							onclick={() => applyDateSort('asc')}
							class="w-full border-l border-gray-300 px-3 py-1.5 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-gray-700"
							class:bg-indigo-600={sortBy === sortByField && sortDir === 'asc'}
							class:text-white={sortBy === sortByField && sortDir === 'asc'}
							class:bg-white={!(sortBy === sortByField && sortDir === 'asc')}
							class:text-gray-900={!(sortBy === sortByField && sortDir === 'asc')}
							class:dark:bg-neutral-900={!(sortBy === sortByField && sortDir === 'asc')}
							class:dark:text-gray-100={!(sortBy === sortByField && sortDir === 'asc')}
							aria-selected={sortBy === sortByField && sortDir === 'asc'}
							role="tab"
						>
							{oldestFirstText}
						</button>
					</div>
				</div>

				{#if activeSummary}
					<div
						class="rounded bg-gray-50 p-2 text-xs text-gray-500 dark:bg-neutral-800 dark:text-gray-300"
					>
						{activeSummary}
					</div>
				{/if}

				<div>
					<label
						for={`${idPrefix}-from`}
						class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
					>
						{fromLabel}
					</label>
					<input
						id={`${idPrefix}-from`}
						type="date"
						bind:value={dateFrom}
						onchange={applyFilter}
						class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none
                     dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
					/>
				</div>
				<div>
					<label
						for={`${idPrefix}-to`}
						class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
					>
						{toLabel}
					</label>
					<input
						id={`${idPrefix}-to`}
						type="date"
						bind:value={dateTo}
						onchange={applyFilter}
						class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none
                     dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
					/>
				</div>
				<div class="flex space-x-2 border-t border-gray-200 pt-2 dark:border-gray-700">
					<button
						type="button"
						onclick={clearFilter}
						class="flex-1 rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200
                   dark:border-gray-700 dark:bg-neutral-800 dark:text-gray-200 dark:hover:bg-neutral-700"
					>
						{clearText}
					</button>
					<button
						type="button"
						onclick={closeDropdown}
						class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
					>
						{closeText}
					</button>
				</div>
			</div>
		</div>
	{/if}
</div>
