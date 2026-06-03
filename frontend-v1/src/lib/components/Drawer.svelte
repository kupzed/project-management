<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import type { Snippet } from 'svelte';
  import { fade, slide } from 'svelte/transition';

  const dispatch = createEventDispatcher<{ close: void }>();

  /**
   * Props for a right-side drawer shell.
   * `show` is bindable; close callbacks support Svelte 5 callers while dispatch keeps legacy pages working.
   */
  let {
    show = $bindable(false),
    title = '',
    width = 'max-w-md',
    children,
    onClose,
    onclose
  }: {
    show?: boolean;
    title?: string;
    width?: string;
    children?: Snippet;
    onClose?: () => void;
    onclose?: () => void;
  } = $props();

  function closeDrawer(): void {
    onClose?.();
    onclose?.();
    dispatch('close');
  }

  function handleBackdropClick(event: MouseEvent): void {
    if (event.target === event.currentTarget) {
      closeDrawer();
    }
  }

  function handleEscapeKey(event: KeyboardEvent): void {
    if (event.key === 'Escape' && show) {
      closeDrawer();
    }
  }
</script>

<svelte:window onkeydown={handleEscapeKey} />

{#if show}
  <div class="fixed inset-0 z-50 overflow-hidden">
    <!-- Backdrop -->
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div
      class="absolute inset-0 bg-black/25 dark:bg-neutral-800/40"
      onclick={handleBackdropClick}
      transition:fade={{ duration: 300 }}
    ></div>

    <div
      class="absolute inset-y-0 right-0 flex max-w-full pl-10"
      transition:slide={{ axis: 'x', duration: 300 }}
    >
      <div
        class="w-screen {width} translate-x-0 transform transition-transform duration-500 ease-in-out"
      >
        <div class="flex h-full flex-col bg-white shadow-xl dark:bg-black">
          <div class="border-b border-gray-100 px-4 py-6 sm:px-6 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-900 dark:text-white">{title}</h2>
              <button
                type="button"
                onclick={closeDrawer}
                class="rounded-md text-gray-500 hover:text-gray-700 focus:ring-2 focus:ring-indigo-500
                       focus:outline-none dark:text-gray-300 dark:hover:text-gray-100 dark:focus:ring-offset-2 dark:focus:ring-offset-gray-800"
              >
                <span class="sr-only">Close panel</span>
                <svg
                  class="h-6 w-6"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>

          <div class="flex-1 overflow-y-auto px-4 text-gray-900 sm:px-6 dark:text-gray-100">
            {#if children}
              {@render children()}
            {/if}
          </div>
        </div>
      </div>
    </div>
  </div>
{/if}
