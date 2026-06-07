<script lang="ts">
  import { formatFileSize } from '$lib/utils/formatters';
  import { getFileIcon, shortenFileName } from './file-attachment';

  /** Editable metadata row for one selected attachment. */
  let {
    file,
    index,
    name,
    description,
    namePlaceholder,
    descriptionPlaceholder,
    selectedText,
    removeText,
    showRemoveButton,
    onNameChange,
    onDescriptionChange,
    onRemove
  }: {
    file: File;
    index: number;
    name: string;
    description: string;
    namePlaceholder: string;
    descriptionPlaceholder: string;
    selectedText: string;
    removeText: string;
    showRemoveButton: boolean;
    onNameChange: (index: number, value: string) => void;
    onDescriptionChange: (index: number, value: string) => void;
    onRemove: (index: number) => void;
  } = $props();
</script>

<div class="flex items-start gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
  <span class="shrink-0 text-xl">{getFileIcon(file.name)}</span>
  <div class="min-w-0 flex-1 space-y-2">
    <input
      type="text"
      value={name}
      required
      onclick={(event) => event.stopPropagation()}
      onmousedown={(event) => event.stopPropagation()}
      oninput={(event) => onNameChange(index, event.currentTarget.value)}
      placeholder={namePlaceholder}
      class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
    />
    <input
      type="text"
      value={description}
      onclick={(event) => event.stopPropagation()}
      onmousedown={(event) => event.stopPropagation()}
      oninput={(event) => onDescriptionChange(index, event.currentTarget.value)}
      placeholder={descriptionPlaceholder}
      class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
    />
    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
      <span title={file.name} class="truncate">{shortenFileName(file.name)}</span>
      <span class="ml-2">{formatFileSize(file.size)}</span>
      <span class="ml-auto flex items-center gap-1">
        <svg class="h-3 w-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13l4 4L19 7"
          />
        </svg>
        {selectedText}
      </span>
    </div>
  </div>
  {#if showRemoveButton}
    <button
      type="button"
      onclick={(event) => {
        event.stopPropagation();
        onRemove(index);
      }}
      class="shrink-0 rounded p-1 text-red-600 transition hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-900/20 dark:hover:text-red-300"
      aria-label={`${removeText} ${file.name}`}
    >
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
        />
      </svg>
    </button>
  {/if}
</div>
