<script lang="ts">
  export type SettingsTab = 'profile' | 'keamanan' | 'role';

  /** Bindable settings tab navigation. */
  let {
    activeTab = $bindable(),
    showRoleTab
  }: {
    activeTab: SettingsTab;
    showRoleTab: boolean;
  } = $props();

  const tabs: Array<{ key: SettingsTab; label: string }> = [
    { key: 'profile', label: 'Profile' },
    { key: 'keamanan', label: 'Keamanan' },
    { key: 'role', label: 'Role' }
  ];
</script>

<div class="mb-4 inline-flex rounded-lg bg-gray-200 p-1 dark:bg-gray-700" role="tablist">
  {#each tabs.filter((tab) => tab.key !== 'role' || showRoleTab) as tab (tab.key)}
    <button
      onclick={() => (activeTab = tab.key)}
      class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-all duration-200 dark:text-gray-200"
      class:bg-white={activeTab === tab.key}
      class:dark:bg-neutral-900={activeTab === tab.key}
      class:shadow={activeTab === tab.key}
      role="tab"
      aria-selected={activeTab === tab.key}
      aria-controls={`panel-${tab.key}`}
      id={`tab-${tab.key}`}
    >
      {tab.label}
    </button>
  {/each}
</div>
