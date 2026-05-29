<script lang="ts">
	import { page } from '$app/stores';
	import { createEventDispatcher } from 'svelte';

	const dispatch = createEventDispatcher<{ click: void }>();

	export let href: string;
	export let icon: string;
	export let label = '';
	export let collapsed = false;
	export let routePrefix = '';

	$: isActive =
		$page.url.pathname === href ||
		$page.url.pathname.startsWith(`${href}/`) ||
		(Boolean(routePrefix) && $page.url.pathname.startsWith(`/${routePrefix}`));

	$: linkClasses = [
		'group flex min-h-10 min-w-0 items-center gap-3 rounded-[10px] px-3 text-sm font-medium transition-colors',
		collapsed ? 'justify-center px-2' : '',
		isActive
			? 'bg-accent text-accent-foreground shadow-sm'
			: 'text-muted-foreground hover:bg-muted hover:text-foreground'
	]
		.filter(Boolean)
		.join(' ');

	$: iconClasses = [
		'h-5 w-5 shrink-0 transition-colors',
		isActive ? 'text-foreground' : 'text-current'
	].join(' ');
</script>

<a
	{href}
	class={linkClasses}
	aria-current={isActive ? 'page' : undefined}
	aria-label={collapsed ? label : undefined}
	title={collapsed ? label : undefined}
	on:click={() => dispatch('click')}
>
	<svg class={iconClasses} fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
		<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d={icon} />
	</svg>
	{#if !collapsed}
		<span class="min-w-0 truncate">
			<slot></slot>
		</span>
	{/if}
</a>
