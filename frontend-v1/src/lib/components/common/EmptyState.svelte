<script lang="ts">
	import type { Snippet } from 'svelte';

	/**
	 * Props for reusable empty states.
	 * Optional snippets allow callers to provide a custom icon and action button.
	 */
	let {
		title,
		description,
		icon,
		action,
		className = '',
		iconClassName = '',
		titleClassName = '',
		descriptionClassName = '',
		actionClassName = 'mt-4'
	}: {
		title: string;
		description?: string;
		icon?: Snippet;
		action?: Snippet;
		className?: string;
		iconClassName?: string;
		titleClassName?: string;
		descriptionClassName?: string;
		actionClassName?: string;
	} = $props();
</script>

<div class="py-16 text-center text-gray-500 dark:text-gray-400 {className}">
	<div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center opacity-30 {iconClassName}">
		{#if icon}
			{@render icon()}
		{:else}
			<svg
				fill="none"
				stroke="currentColor"
				viewBox="0 0 24 24"
				class="h-10 w-10"
				aria-hidden="true"
			>
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="1.8"
					d="M20 13V7a2 2 0 00-2-2h-3.5L12 3 9.5 5H6a2 2 0 00-2 2v6m16 0v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4m16 0h-4.5a2 2 0 00-1.8 1.1L13 15.5h-2l-.7-1.4A2 2 0 008.5 13H4"
				></path>
			</svg>
		{/if}
	</div>
	<p class="text-sm {titleClassName}">{title}</p>
	{#if description}
		<p class="mt-1 text-xs {descriptionClassName}">{description}</p>
	{/if}
	{#if action}
		<div class={actionClassName}>{@render action()}</div>
	{/if}
</div>
