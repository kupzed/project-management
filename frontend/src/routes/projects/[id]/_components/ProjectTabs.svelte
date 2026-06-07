<script lang="ts">
  export type ProjectDetailTab = 'detail' | 'activity' | 'certificates';

  /**
   * Bindable tab state for the project detail page navigation.
   */
  let {
    activeTab = $bindable('activity' as ProjectDetailTab),
    showCertificateTab
  }: {
    activeTab?: ProjectDetailTab;
    showCertificateTab: boolean;
  } = $props();

  function selectTab(tab: ProjectDetailTab): void {
    activeTab = tab;
  }
</script>

<div class="mb-4 flex min-w-0 items-center justify-between">
  <div class="inline-flex rounded-lg bg-gray-200 p-1 dark:bg-gray-700" role="tablist">
    <button
      type="button"
      onclick={() => selectTab('detail')}
      class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-all duration-200 dark:text-gray-200"
      class:bg-white={activeTab === 'detail'}
      class:dark:bg-neutral-900={activeTab === 'detail'}
      class:shadow={activeTab === 'detail'}
      role="tab"
      aria-selected={activeTab === 'detail'}
    >
      Detail
    </button>
    <button
      type="button"
      onclick={() => selectTab('activity')}
      class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-all duration-200 dark:text-gray-200"
      class:bg-white={activeTab === 'activity'}
      class:dark:bg-neutral-900={activeTab === 'activity'}
      class:shadow={activeTab === 'activity'}
      role="tab"
      aria-selected={activeTab === 'activity'}
    >
      Activity
    </button>
    {#if showCertificateTab}
      <button
        type="button"
        onclick={() => selectTab('certificates')}
        class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-all duration-200 dark:text-gray-200"
        class:bg-white={activeTab === 'certificates'}
        class:dark:bg-neutral-900={activeTab === 'certificates'}
        class:shadow={activeTab === 'certificates'}
        role="tab"
        aria-selected={activeTab === 'certificates'}
      >
        Certificate
      </button>
    {/if}
  </div>
</div>
