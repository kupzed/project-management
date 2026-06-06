<script lang="ts">
  /**
   * Icon-only row actions for detail/edit/delete controls.
   */
  let {
    itemName,
    canUpdate = false,
    canDelete = false,
    onDetail,
    onEdit,
    onDelete
  }: {
    itemName: string;
    canUpdate?: boolean;
    canDelete?: boolean;
    onDetail: () => void;
    onEdit?: () => void;
    onDelete?: () => void | Promise<void>;
  } = $props();
</script>

<div class="flex items-center justify-end space-x-2">
  <button
    type="button"
    onclick={onDetail}
    class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300"
    title="Detail"
    aria-label={`Detail, ${itemName}`}
  >
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width="20"
      height="20"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
      <circle cx="12" cy="12" r="3"></circle>
    </svg>
    <span class="sr-only">Detail, {itemName}</span>
  </button>

  {#if canUpdate}
    <button
      type="button"
      onclick={onEdit}
      class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
      title="Edit"
      aria-label={`Edit, ${itemName}`}
    >
      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
        ></path>
      </svg>
      <span class="sr-only">Edit, {itemName}</span>
    </button>
  {/if}

  {#if canDelete}
    <button
      type="button"
      onclick={onDelete}
      class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
      title="Hapus"
      aria-label={`Hapus, ${itemName}`}
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <polyline points="3 6 5 6 21 6"></polyline>
        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
        ></path>
        <line x1="10" y1="11" x2="10" y2="17"></line>
        <line x1="14" y1="11" x2="14" y2="17"></line>
      </svg>
      <span class="sr-only">Hapus, {itemName}</span>
    </button>
  {/if}
</div>
