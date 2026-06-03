<script lang="ts">
	export type ViewToggleView = 'table' | 'list';

	/**
	 * Props for a segmented table/list view switcher.
	 * `activeView` is bindable so parent pages can keep their current view state.
	 */
	let {
		activeView = $bindable('table' as ViewToggleView),
		views = ['table', 'list'] as ViewToggleView[],
		ariaLabel = 'Switch view',
		tableLabel = 'Table',
		listLabel = 'List',
		tableSrLabel = 'Tampilan Tabel',
		listSrLabel = 'Tampilan List'
	}: {
		activeView?: ViewToggleView;
		views?: ViewToggleView[];
		ariaLabel?: string;
		tableLabel?: string;
		listLabel?: string;
		tableSrLabel?: string;
		listSrLabel?: string;
	} = $props();

	let normalizedViews = $derived(
		views.length > 0 ? views : (['table', 'list'] as ViewToggleView[])
	);

	function setActiveView(view: ViewToggleView): void {
		activeView = view;
	}

	function handleKeydown(event: KeyboardEvent): void {
		if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
			return;
		}

		event.preventDefault();
		const currentIndex = normalizedViews.indexOf(activeView);
		const fallbackIndex = currentIndex === -1 ? 0 : currentIndex;
		const direction = event.key === 'ArrowRight' ? 1 : -1;
		const nextIndex = (fallbackIndex + direction + normalizedViews.length) % normalizedViews.length;
		activeView = normalizedViews[nextIndex];
	}

	function getLabel(view: ViewToggleView): string {
		return view === 'table' ? tableLabel : listLabel;
	}

	function getSrLabel(view: ViewToggleView): string {
		return view === 'table' ? tableSrLabel : listSrLabel;
	}
</script>

<div
	class="inline-flex gap-1 rounded-md bg-white dark:bg-neutral-900"
	role="tablist"
	aria-label={ariaLabel}
	tabindex="0"
	onkeydown={handleKeydown}
>
	{#each normalizedViews as view}
		<button
			type="button"
			onclick={() => setActiveView(view)}
			class="grid h-9 w-9 place-items-center rounded-md
             text-gray-600 hover:text-gray-900 focus:outline-none focus-visible:ring-2
             focus-visible:ring-gray-400 dark:text-gray-300 dark:hover:text-gray-50 dark:focus-visible:ring-gray-500"
			class:bg-white={activeView === view}
			class:dark:bg-neutral-900={activeView === view}
			class:text-gray-900={activeView === view}
			class:dark:text-white={activeView === view}
			class:border={activeView === view}
			class:border-gray-300={activeView === view}
			class:dark:border-gray-700={activeView === view}
			role="tab"
			aria-selected={activeView === view}
			aria-label={`${getLabel(view)} view`}
			title={getLabel(view)}
		>
			{#if view === 'table'}
				<svg
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					stroke-width="1.8"
					stroke-linecap="round"
					stroke-linejoin="round"
					width="18"
					height="18"
					aria-hidden="true"
				>
					<rect x="3.5" y="4.5" width="17" height="15" rx="2"></rect>
					<line x1="3.5" y1="9" x2="20.5" y2="9"></line>
					<line x1="3.5" y1="13" x2="20.5" y2="13"></line>
					<line x1="3.5" y1="17" x2="20.5" y2="17"></line>
				</svg>
			{:else}
				<svg
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					stroke-width="1.8"
					stroke-linecap="round"
					stroke-linejoin="round"
					width="18"
					height="18"
					aria-hidden="true"
				>
					<circle cx="5" cy="6" r="1.3"></circle>
					<circle cx="5" cy="12" r="1.3"></circle>
					<circle cx="5" cy="18" r="1.3"></circle>
					<line x1="9" y1="6" x2="20" y2="6"></line>
					<line x1="9" y1="12" x2="20" y2="12"></line>
					<line x1="9" y1="18" x2="20" y2="18"></line>
				</svg>
			{/if}
			<span class="sr-only">{getSrLabel(view)}</span>
		</button>
	{/each}
</div>
