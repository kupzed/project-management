<script lang="ts">
  import '../../app.css';
  import { onMount, type Snippet } from 'svelte';
  import { theme } from '$lib/stores/theme';

  let { children }: { children: Snippet } = $props();

  // Pastikan efek 'html.dark' terpasang juga di layout auth
  onMount(() => {
    const unsub = theme.subscribe(() => {});
    return unsub;
  });
</script>

<main class="min-h-screen bg-background text-foreground">
  {@render children()}
</main>

<style>
  /* Anda bisa menambahkan gaya CSS global di sini jika diperlukan */
</style>
