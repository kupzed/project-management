<script lang="ts">
	import '../../app.css';
	import { onMount } from 'svelte';
	import { theme } from '$lib/stores/theme';

	// Pastikan efek 'html.dark' terpasang juga di layout auth
	onMount(() => {
		const unsub = theme.subscribe(() => {});
		return unsub;
	});
</script>

<main class="min-h-screen bg-background text-foreground">
	<slot></slot>
</main>

<style>
	/* Anda bisa menambahkan gaya CSS global di sini jika diperlukan */
</style>
