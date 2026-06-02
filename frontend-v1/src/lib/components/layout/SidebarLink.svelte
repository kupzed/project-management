<script lang="ts">
	import { page } from '$app/state';
	import type { Snippet } from 'svelte';

	type Props = {
		href: string;
		icon: string;
		label?: string;
		collapsed?: boolean;
		routePrefix?: string;
		onclick?: (event: MouseEvent) => void;
		children?: Snippet;
	};

	let {
		href,
		icon,
		label = '',
		collapsed = false,
		routePrefix = '',
		onclick,
		children
	}: Props = $props();

	let isActive = $derived(
		page.url.pathname === href ||
			page.url.pathname.startsWith(`${href}/`) ||
			(Boolean(routePrefix) && page.url.pathname.startsWith(`/${routePrefix}`))
	);

	let linkClasses = $derived(
		[
			'group flex min-h-10 min-w-0 items-center gap-3 rounded-[10px] px-3 text-sm font-medium transition-colors',
			collapsed ? 'justify-center px-2' : '',
			isActive
				? 'bg-accent text-accent-foreground shadow-sm'
				: 'text-muted-foreground hover:bg-muted hover:text-foreground'
		]
			.filter(Boolean)
			.join(' ')
	);

	let iconClasses = $derived(
		['h-5 w-5 shrink-0 transition-colors', isActive ? 'text-foreground' : 'text-current'].join(' ')
	);
</script>

<a
	{href}
	class={linkClasses}
	aria-current={isActive ? 'page' : undefined}
	aria-label={collapsed ? label : undefined}
	title={collapsed ? label : undefined}
	{onclick}
>
	<svg class={iconClasses} fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
		<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d={icon} />
	</svg>
	{#if !collapsed}
		<span class="min-w-0 truncate">
			{@render children?.()}
		</span>
	{/if}
</a>
