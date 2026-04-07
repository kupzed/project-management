<script lang="ts">
  import { createEventDispatcher, onMount, onDestroy } from 'svelte';
  import { fade } from 'svelte/transition';
  import { goto } from '$app/navigation';
  import { theme, toggleTheme } from '$lib/stores/theme';
  import { currentUser } from '$lib/stores/user';

  const dispatch = createEventDispatcher();

  let showUserDropdown = false;

  onMount(() => {
    document.addEventListener('click', handleClickOutside);
  });

  onDestroy(() => {
    document.removeEventListener('click', handleClickOutside);
  });

  function toggleMobileSidebar() {
    dispatch('toggleMobileSidebar');
  }

  function handleClickOutside(event: MouseEvent) {
    if (
      showUserDropdown &&
      event.target &&
      !(event.target as HTMLElement).closest('#user-menu-button, #user-dropdown-menu')
    ) {
      showUserDropdown = false;
    }
  }

  function openSettings() {
    showUserDropdown = false;
    goto('/settings');
  }

  function doLogout() {
    showUserDropdown = false;
    dispatch('logout'); // parent tetap yang eksekusi API logout & redirect
  }
</script>

<header class="sticky top-0 z-10 flex h-16 flex-shrink-0 bg-white dark:bg-black text-gray-900 dark:text-white shadow">
  <!-- svelte-ignore a11y_consider_explicit_label -->
  <button
    type="button"
    class="ml-4 text-gray-500 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:ring-offset-2 lg:hidden"
    on:click={toggleMobileSidebar}
    aria-label="Toggle sidebar"
  >
    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
  </button>

  <div class="flex flex-1 justify-between px-4 py-4">
    <div class="flex flex-1 items-center">
      <slot name="topnav-title"></slot>
    </div>

    <div class="ml-4 flex items-center md:ml-6">
      <!-- Toggle dark/light mode -->
      <button
        aria-label="Toggle dark mode"
        class="mr-2 p-1 rounded-full text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
        on:click={toggleTheme}
      >
        {#if $theme === 'dark'}
          <!-- Ikon bulan untuk mode gelap -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 12.79A9 9 0 1111.21 3c-.09.88.27 1.75.92 2.4a5 5 0 006.47 6.47c.65.65 1.52 1.01 2.4.92z" />
          </svg>
        {:else}
          <!-- Ikon matahari untuk mode terang -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 2v2m6 4l-1.41 1.41M20 12h-2m-4 6l1.41 1.41M12 20v-2m-6-4l1.41-1.41M4 12H2m4-4L4.59 6.59M12 8a4 4 0 100 8 4 4 0 000-8z" />
          </svg>
        {/if}
      </button>

      <!-- User dropdown -->
      <div class="relative" id="user-dropdown-container">
        <div>
          <!-- svelte-ignore a11y_consider_explicit_label -->
          <button
            type="button"
            class="flex max-w-xs items-center rounded-full text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:ring-offset-2"
            id="user-menu-button"
            aria-expanded={showUserDropdown}
            aria-haspopup="true"
            on:click={() => (showUserDropdown = !showUserDropdown)}
          >
            <!-- avatar placeholder -->
            <svg class="size-10 text-gray-500 dark:text-gray-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
            </svg>
            <svg class="ml-1 h-5 w-5 text-gray-500 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
        </div>

        {#if showUserDropdown}
          <div
            id="user-dropdown-menu"
            class="absolute right-0 z-10 mt-2 w-64 origin-top-right overflow-hidden rounded-xl bg-white dark:bg-neutral-900 shadow-lg ring-1 ring-black/5 focus:outline-hidden"
            role="menu"
            aria-orientation="vertical"
            aria-labelledby="user-menu-button"
            tabindex="-1"
            transition:fade={{ duration: 100 }}
          >
            <!-- Header: Nama & Email -->
            <div class="px-4 py-3">
              <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {$currentUser ? $currentUser.name : 'Pengguna'}
              </p>
              <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 truncate">
                {$currentUser ? $currentUser.email : '—'}
              </p>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            <!-- Settings -->
            <button
              type="button"
              on:click={openSettings}
              class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800"
              role="menuitem"
              tabindex="-1"
            >
              Pengaturan
            </button>

            <!-- Logout -->
            <button
              type="button"
              on:click={doLogout}
              class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white dark:hover:bg-red-600"
              role="menuitem"
              tabindex="-1"
            >
              Logout
            </button>
          </div>
        {/if}
      </div>
    </div>
  </div>
</header>

<style>
  /* opsional: kecilkan tap target di layar kecil */
  @media (max-width: 420px) {
    #user-menu-button svg.size-10 { width: 32px; height: 32px; }
  }
</style>
