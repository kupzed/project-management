<script lang="ts">
  import type { Snippet } from 'svelte';
  import { lockBodyScroll } from '$lib/utils/scroll-lock';

  /**
   * Props for a reusable modal shell.
   * `show` is bindable and close callbacks support Svelte 5 callers.
   */
  let {
    show = $bindable(false),
    title = '',
    maxWidth = 'max-w-lg',
    children,
    onClose,
    onclose
  }: {
    show?: boolean;
    title?: string;
    maxWidth?: string;
    children?: Snippet;
    onClose?: () => void;
    onclose?: () => void;
  } = $props();

  $effect(() => {
    if (show) {
      lockBodyScroll(true);
      return () => {
        lockBodyScroll(false);
      };
    }
  });

  function closeModal(): void {
    show = false;
    onClose?.();
    onclose?.();
  }

  function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
      closeModal();
    }
  }
</script>

<svelte:window onkeydown={handleKeydown} />

{#if show}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/25 p-8 dark:bg-neutral-800/40"
  >
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div class="absolute inset-0" onclick={closeModal}></div>

    <div
      class="w-full rounded-lg border border-transparent bg-white shadow-lg dark:border-gray-800 dark:bg-black {maxWidth} relative max-h-[90vh] overflow-y-auto p-6 text-gray-900 dark:text-gray-100"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
    >
      <button
        id="close-modal-btn"
        class="absolute top-2 right-2 text-2xl text-gray-500 hover:text-gray-700 focus:ring-2 focus:ring-indigo-500
               focus:outline-none dark:text-gray-300 dark:hover:text-gray-100 dark:focus:ring-offset-2 dark:focus:ring-offset-gray-800"
        onclick={closeModal}
        aria-label="Close modal"
      >
        &times;
      </button>
      <h2
        id="modal-title"
        class="mb-6 text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
      >
        {title}
      </h2>
      {#if children}
        {@render children()}
      {/if}
    </div>
  </div>
{/if}

<style>
  /* tetap kosong; util Tailwind dipakai */
</style>
