<script lang="ts">
	import { createEventDispatcher } from 'svelte';
	import { fade, slide } from 'svelte/transition';
	import AppSidebar from './AppSidebar.svelte';

	const dispatch = createEventDispatcher<{
		close: void;
		logout: void;
	}>();

	export let open = false;
</script>

{#if open}
	<div class="relative z-50 lg:hidden" aria-modal="true" role="dialog">
		<div
			class="fixed inset-0 bg-slate-950/35 backdrop-blur-[2px] dark:bg-black/55"
			transition:fade={{ duration: 150 }}
			on:click={() => dispatch('close')}
			aria-hidden="true"
		></div>

		<div class="fixed inset-0 z-50 flex">
			<div
				class="relative flex h-full w-full max-w-[18rem] flex-1 flex-col overflow-hidden border-r border-border bg-card shadow-2xl"
				in:slide={{ axis: 'x', duration: 220 }}
				out:slide={{ axis: 'x', duration: 180 }}
			>
				<AppSidebar
					mobile
					on:close={() => dispatch('close')}
					on:logout={() => dispatch('logout')}
				/>
			</div>
		</div>
	</div>
{/if}
