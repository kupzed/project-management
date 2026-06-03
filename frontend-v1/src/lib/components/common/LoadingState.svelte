<script lang="ts">
	/**
	 * Props for reusable loading states.
	 * `label` and `description` control all visible copy.
	 */
	let {
		label = 'Memuat...',
		description,
		size = 'md',
		className = ''
	}: {
		label?: string;
		description?: string;
		size?: 'sm' | 'md' | 'lg';
		className?: string;
	} = $props();

	let spinnerSize = $derived(size === 'sm' ? 'h-5 w-5' : size === 'lg' ? 'h-10 w-10' : 'h-8 w-8');
</script>

<div
	class="flex flex-col items-center justify-center gap-3 py-12 text-gray-500 dark:text-gray-400 {className}"
>
	<div
		class="{spinnerSize} animate-spin rounded-full border-2 border-gray-200 border-t-indigo-600 dark:border-neutral-700 dark:border-t-indigo-400"
		aria-hidden="true"
	></div>
	<div class="text-center">
		<p class="text-sm font-medium text-gray-700 dark:text-gray-200">{label}</p>
		{#if description}
			<p class="mt-1 text-xs">{description}</p>
		{/if}
	</div>
</div>
