<script lang="ts">
  /**
   * Props for a controlled search input with built-in debounce.
   * `value` is bindable; `onSearch` receives the debounced value.
   */
  let {
    value = $bindable(''),
    placeholder = 'Cari...',
    debounceMs = 800,
    onSearch,
    id,
    name,
    disabled = false,
    ariaLabel = placeholder
  }: {
    value?: string;
    placeholder?: string;
    debounceMs?: number;
    onSearch?: (value: string) => void;
    id?: string;
    name?: string;
    disabled?: boolean;
    ariaLabel?: string;
  } = $props();

  let hasInitialized = false;

  $effect(() => {
    const currentValue = value;
    const currentDebounce = debounceMs;

    if (!onSearch) {
      return;
    }

    if (!hasInitialized) {
      hasInitialized = true;
      return;
    }

    const timer = window.setTimeout(() => onSearch(currentValue), Math.max(0, currentDebounce));

    return () => window.clearTimeout(timer);
  });
</script>

<div class="relative w-full">
  <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
    <svg
      class="h-4 w-4 text-gray-400"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"
      ></path>
    </svg>
  </div>
  <input
    {id}
    {name}
    type="text"
    bind:value
    {placeholder}
    {disabled}
    aria-label={ariaLabel}
    class="w-full rounded-md border border-gray-300 bg-white py-2 pr-3 pl-10 text-sm text-gray-900 placeholder-gray-500
           focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none
           disabled:cursor-not-allowed disabled:opacity-60
           dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100 dark:placeholder-gray-400"
  />
</div>
