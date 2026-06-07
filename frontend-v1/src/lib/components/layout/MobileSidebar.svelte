<script lang="ts">
  import { fade, slide } from 'svelte/transition';
  import AppSidebar from './AppSidebar.svelte';

  type Props = {
    open?: boolean;
    close?: () => void;
    logout?: () => void;
  };

  const noop = () => {};

  let { open = $bindable(false), close = noop, logout = noop }: Props = $props();

  function closeSidebar() {
    close();
  }
</script>

{#if open}
  <div class="relative z-50 lg:hidden" aria-modal="true" role="dialog">
    <div
      class="fixed inset-0 bg-slate-950/35 backdrop-blur-[2px] dark:bg-black/55"
      transition:fade={{ duration: 150 }}
      onclick={closeSidebar}
      aria-hidden="true"
    ></div>

    <div class="fixed inset-0 z-50 flex">
      <div
        class="relative flex h-full w-full max-w-[18rem] flex-1 flex-col overflow-hidden border-r border-border bg-card shadow-2xl"
        in:slide={{ axis: 'x', duration: 220 }}
        out:slide={{ axis: 'x', duration: 180 }}
      >
        <AppSidebar mobile close={closeSidebar} {logout} />
      </div>
    </div>
  </div>
{/if}
