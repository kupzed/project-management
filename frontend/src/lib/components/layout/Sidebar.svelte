<script lang="ts">
  import AppSidebar from './AppSidebar.svelte';

  type Props = {
    collapsed?: boolean;
    logout?: () => void;
    toggleCollapsed?: () => void;
  };

  const noop = () => {};

  let { collapsed = $bindable(false), logout = noop, toggleCollapsed = noop }: Props = $props();

  let widthClass = $derived(collapsed ? 'lg:w-20' : 'lg:w-64');
</script>

<aside
  class={`hidden border-r border-border bg-card shadow-[0_0_0_1px_rgba(15,23,42,0.02)] transition-[width] duration-200 ease-linear lg:fixed lg:inset-y-0 lg:z-30 lg:flex lg:flex-col ${widthClass}`}
>
  <AppSidebar {collapsed} {toggleCollapsed} {logout} />
</aside>
