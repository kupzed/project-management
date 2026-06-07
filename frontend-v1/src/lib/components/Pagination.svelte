<script lang="ts">
  /**
   * Props for reusable pagination controls with optional per-page selection.
   */
  let {
    currentPage,
    lastPage,
    totalItems,
    itemsPerPage = 10,
    perPageOptions = [10, 25, 50, 100],
    onPerPageChange = () => {},
    onPageChange = () => {},
    showResultsInfo = true,
    showMobileButtons = true
  }: {
    currentPage: number;
    lastPage: number;
    totalItems: number;
    itemsPerPage?: number;
    perPageOptions?: number[];
    onPerPageChange?: (n: number) => void;
    onPageChange?: (page: number) => void;
    showResultsInfo?: boolean;
    showMobileButtons?: boolean;
  } = $props();

  let startItem = $derived((currentPage - 1) * itemsPerPage + 1);
  let endItem = $derived(Math.min(currentPage * itemsPerPage, totalItems));

  function goToPage(page: number): void {
    if (page >= 1 && page <= lastPage && page !== currentPage) {
      onPageChange(page);
    }
  }

  function handlePerPageChange(event: Event): void {
    const n = parseInt((event.target as HTMLSelectElement).value, 10);
    if (!Number.isNaN(n) && n !== itemsPerPage) {
      onPerPageChange(n);
    }
  }
</script>

{#if totalItems > 0}
  <div
    class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 dark:border-gray-700 dark:bg-black"
  >
    {#if showMobileButtons}
      <!-- Mobile -->
      <div class="flex w-full flex-col items-center justify-center gap-2 text-center sm:hidden">
        {#if showResultsInfo}
          <p class="text-xs text-gray-700 dark:text-gray-300">
            Showing
            <select
              value={itemsPerPage}
              onchange={handlePerPageChange}
              class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-gray-900
                     dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
            >
              {#each perPageOptions as opt (opt)}
                <option value={opt}>{opt}</option>
              {/each}
            </select>
            Records (Showing <span class="font-bold">{startItem}</span> to
            <span class="font-bold">{endItem}</span>
            of
            <span class="font-bold">{totalItems}</span>)
          </p>
        {/if}

        <nav class="flex items-center justify-center space-x-1" aria-label="Pagination">
          <button
            onclick={() => goToPage(1)}
            disabled={currentPage === 1}
            class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50
                  focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none
                  dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100 dark:hover:bg-neutral-800
                  {currentPage === 1 ? 'cursor-not-allowed opacity-50' : ''}"
          >
            First
          </button>

          <button
            onclick={() => goToPage(currentPage - 1)}
            aria-label="Halaman sebelumnya"
            disabled={currentPage === 1}
            class="rounded-md border border-gray-300 bg-white p-1 text-gray-700 hover:bg-gray-50
                  focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none
                  dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100 dark:hover:bg-neutral-800
                  {currentPage === 1 ? 'cursor-not-allowed opacity-50' : ''}"
          >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z"
                clip-rule="evenodd"
              />
            </svg>
          </button>

          <span
            class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700
                      dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          >
            Page {currentPage} of {lastPage}
          </span>

          <button
            onclick={() => goToPage(currentPage + 1)}
            aria-label="Halaman berikutnya"
            disabled={currentPage === lastPage}
            class="rounded-md border border-gray-300 bg-white p-1 text-gray-700 hover:bg-gray-50
                  focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none
                  dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100 dark:hover:bg-neutral-800
                  {currentPage === lastPage ? 'cursor-not-allowed opacity-50' : ''}"
          >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                clip-rule="evenodd"
              />
            </svg>
          </button>

          <button
            onclick={() => goToPage(lastPage)}
            disabled={currentPage === lastPage}
            class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50
                  focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none
                  dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100 dark:hover:bg-neutral-800
                  {currentPage === lastPage ? 'cursor-not-allowed opacity-50' : ''}"
          >
            Last
          </button>
        </nav>
      </div>
    {/if}

    <!-- Desktop -->
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        {#if showResultsInfo}
          <p class="text-xs text-gray-700 dark:text-gray-300">
            Showing
            <select
              value={itemsPerPage}
              onchange={handlePerPageChange}
              class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-gray-900
                    dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
            >
              {#each perPageOptions as opt (opt)}
                <option value={opt}>{opt}</option>
              {/each}
            </select>
            Records (Showing <span class="font-bold">{startItem}</span> to
            <span class="font-bold">{endItem}</span>
            of
            <span class="font-bold">{totalItems}</span>)
          </p>
        {/if}
      </div>

      <div>
        <nav class="flex items-center space-x-2" aria-label="Pagination">
          <button
            onclick={() => goToPage(1)}
            disabled={currentPage === 1}
            class="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50
                   focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none
                   dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100 dark:hover:bg-neutral-800
                   {currentPage === 1 ? 'cursor-not-allowed opacity-50' : ''}"
          >
            First
          </button>

          <button
            onclick={() => goToPage(currentPage - 1)}
            aria-label="Halaman sebelumnya"
            disabled={currentPage === 1}
            class="rounded-md border border-gray-300 bg-white p-1.5 text-gray-700 hover:bg-gray-50
                   focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none
                   dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100 dark:hover:bg-neutral-800
                   {currentPage === 1 ? 'cursor-not-allowed opacity-50' : ''}"
          >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z"
                clip-rule="evenodd"
              />
            </svg>
          </button>

          <span
            class="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm font-medium text-gray-700
                       dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
          >
            Page {currentPage} of {lastPage}
          </span>

          <button
            onclick={() => goToPage(currentPage + 1)}
            aria-label="Halaman berikutnya"
            disabled={currentPage === lastPage}
            class="rounded-md border border-gray-300 bg-white p-1.5 text-gray-700 hover:bg-gray-50
                   focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none
                   dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100 dark:hover:bg-neutral-800
                   {currentPage === lastPage ? 'cursor-not-allowed opacity-50' : ''}"
          >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                clip-rule="evenodd"
              />
            </svg>
          </button>

          <button
            onclick={() => goToPage(lastPage)}
            disabled={currentPage === lastPage}
            class="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50
                   focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none
                   dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100 dark:hover:bg-neutral-800
                   {currentPage === lastPage ? 'cursor-not-allowed opacity-50' : ''}"
          >
            Last
          </button>
        </nav>
      </div>
    </div>
  </div>
{/if}
