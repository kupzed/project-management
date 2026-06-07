<script lang="ts">
  import type { Mitra } from '$lib/types';

  /**
   * Renders all active mitra category badges.
   */
  let { mitra }: { mitra: Mitra } = $props();

  const badgeClass =
    'inline-flex rounded-full bg-indigo-100 px-2 text-xs leading-5 font-semibold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200';
</script>

<div class="flex flex-wrap gap-1">
  {#if mitra.is_pribadi}
    <span class={badgeClass}>Pribadi</span>
  {/if}
  {#if mitra.is_perusahaan}
    <span class={badgeClass}>Perusahaan</span>
  {/if}
  {#if mitra.is_customer}
    <span class={badgeClass}>Customer</span>
  {/if}
  {#if mitra.is_vendor}
    <span class={badgeClass}>Vendor</span>
  {/if}
</div>
