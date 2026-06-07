<script lang="ts">
  import { formatFileSize } from '$lib/utils/formatters';
  import FileAttachmentItem from './FileAttachmentItem.svelte';
  import {
    dedupeFiles,
    validateFileSizes,
    type FileAttachmentDescriptionPayload,
    type FileAttachmentNamePayload,
    type FileAttachmentPayload,
    type FileAttachmentRemovePayload
  } from './file-attachment';

  /**
   * Props for multi-file upload with bindable files, names, and descriptions.
   * Callback props are the Svelte 5 API.
   */
  let {
    label = 'Attachment Files',
    id = 'file-attachment',
    accept = 'image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.*,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain',
    files = $bindable([]),
    fileNames = $bindable([]),
    fileDescriptions = $bindable([]),
    placeholder = 'PDF, PNG, JPG, DOC, DOCX, XLS, XLSX, TXT, maksimal 10MB per file',
    maxFiles = 10,
    showRemoveButton = true,
    selectedFileText = 'File terpilih',
    maxSizeBytes = 10 * 1024 * 1024,
    optionalText = 'Opsional',
    maxFilesText = 'Maksimal',
    addFileText = 'Tambah file',
    removeAllText = 'Hapus semua',
    uploadText = 'Klik untuk upload files',
    dropText = 'atau drag & drop',
    fileNamePlaceholder = 'Nama file',
    fileDescriptionPlaceholder = 'Deskripsi file',
    invalidFileText = 'File tidak valid.',
    fileCountText = 'file terpilih',
    totalText = 'Total',
    removeFileText = 'Hapus',
    onChange,
    onchange,
    onRemove,
    onremove,
    onClear,
    onclear,
    onNameChange,
    onnameChange,
    onDescriptionChange,
    ondescriptionChange
  }: {
    label?: string;
    id?: string;
    accept?: string;
    files?: File[];
    fileNames?: string[];
    fileDescriptions?: string[];
    placeholder?: string;
    maxFiles?: number;
    showRemoveButton?: boolean;
    selectedFileText?: string;
    maxSizeBytes?: number;
    optionalText?: string;
    maxFilesText?: string;
    addFileText?: string;
    removeAllText?: string;
    uploadText?: string;
    dropText?: string;
    fileNamePlaceholder?: string;
    fileDescriptionPlaceholder?: string;
    invalidFileText?: string;
    fileCountText?: string;
    totalText?: string;
    removeFileText?: string;
    onChange?: (payload: FileAttachmentPayload) => void;
    onchange?: (payload: FileAttachmentPayload) => void;
    onRemove?: (payload: FileAttachmentRemovePayload) => void;
    onremove?: (payload: FileAttachmentRemovePayload) => void;
    onClear?: () => void;
    onclear?: () => void;
    onNameChange?: (payload: FileAttachmentNamePayload) => void;
    onnameChange?: (payload: FileAttachmentNamePayload) => void;
    onDescriptionChange?: (payload: FileAttachmentDescriptionPayload) => void;
    ondescriptionChange?: (payload: FileAttachmentDescriptionPayload) => void;
  } = $props();

  let fileInput: HTMLInputElement | undefined = $state();
  let isDragOver = $state(false);
  let errorMsg = $state<string | null>(null);

  let totalSize = $derived(files.reduce((total, file) => total + file.size, 0));
  let canAddMore = $derived(files.length < maxFiles);

  function getPayload(): FileAttachmentPayload {
    return { files, fileNames, fileDescriptions };
  }

  function syncToInput(current: File[]): void {
    if (!fileInput) {
      return;
    }

    if (current.length > 0) {
      const dataTransfer = new DataTransfer();
      current.forEach((file) => dataTransfer.items.add(file));
      fileInput.files = dataTransfer.files;
      return;
    }

    fileInput.value = '';
  }

  function emitChange(): void {
    const payload = getPayload();
    onChange?.(payload);
    onchange?.(payload);
  }

  function applyFiles(nextFiles: File[]): void {
    const previousNames = fileNames;
    const previousDescriptions = fileDescriptions;

    files = nextFiles;
    fileNames = files.map((file, index) => previousNames[index] ?? file.name);
    fileDescriptions = files.map((_, index) => previousDescriptions[index] ?? '');
    syncToInput(files);
    emitChange();
  }

  function appendFiles(newFiles: File[]): void {
    errorMsg = null;
    const combined = dedupeFiles([...files, ...newFiles]);
    const limited = combined.slice(0, maxFiles);
    const { ok, reason } = validateFileSizes(limited, maxSizeBytes);

    if (!ok) {
      errorMsg = reason ?? invalidFileText;
      return;
    }

    applyFiles(limited);
  }

  function handleFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const selectedFiles = Array.from(input.files || []);
    appendFiles(selectedFiles);
  }

  function handleRemoveFile(index: number): void {
    files = files.filter((_, itemIndex) => itemIndex !== index);
    fileNames = fileNames.filter((_, itemIndex) => itemIndex !== index);
    fileDescriptions = fileDescriptions.filter((_, itemIndex) => itemIndex !== index);
    syncToInput(files);
    emitChange();

    const payload = { index, ...getPayload() };
    onRemove?.(payload);
    onremove?.(payload);
  }

  function handleRemoveAll(): void {
    files = [];
    fileNames = [];
    fileDescriptions = [];
    syncToInput(files);
    emitChange();
    onClear?.();
    onclear?.();
  }

  function handleDragEnter(): void {
    isDragOver = true;
  }

  function handleDragOver(event: DragEvent): void {
    event.preventDefault();
    isDragOver = true;
  }

  function handleDragLeave(event: DragEvent): void {
    if (event.currentTarget === event.target) {
      isDragOver = false;
    }
  }

  function handleDrop(event: DragEvent): void {
    event.preventDefault();
    isDragOver = false;
    const droppedFiles = Array.from(event.dataTransfer?.files || []);
    if (droppedFiles.length > 0) {
      appendFiles(droppedFiles);
    }
  }

  function triggerPicker(): void {
    fileInput?.click();
  }

  function updateFileName(index: number, newName: string): void {
    fileNames = fileNames.map((name, itemIndex) => (itemIndex === index ? newName : name));
    const payload = { index, name: newName };
    onNameChange?.(payload);
    onnameChange?.(payload);
    emitChange();
  }

  function updateFileDesc(index: number, newVal: string): void {
    fileDescriptions = fileDescriptions.map((description, itemIndex) =>
      itemIndex === index ? newVal : description
    );
    const payload = { index, description: newVal };
    onDescriptionChange?.(payload);
    ondescriptionChange?.(payload);
    emitChange();
  }

  function handleContainerClick(event: MouseEvent): void {
    const target = event.target as HTMLElement;
    if (target.closest('input, textarea, button, [role="button"], a, svg')) {
      return;
    }

    triggerPicker();
  }
</script>

<div class="w-full">
  <div class="flex items-center justify-between gap-3">
    <label for={id} class="block text-sm/6 font-medium text-gray-900 dark:text-white">
      {label} ({optionalText}) - {maxFilesText}
      {maxFiles} file
    </label>

    <div class="flex items-center gap-2">
      {#if canAddMore}
        <button
          type="button"
          onclick={triggerPicker}
          class="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-indigo-500"
        >
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
              d="M12 4v16M4 12h16"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
          </svg>
          {addFileText}
        </button>
      {/if}

      {#if files.length > 0}
        <button
          type="button"
          onclick={handleRemoveAll}
          class="inline-flex items-center gap-1 rounded-md bg-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
        >
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
              d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          {removeAllText}
        </button>
      {/if}
    </div>
  </div>

  <input
    {id}
    type="file"
    {accept}
    multiple
    class="sr-only"
    bind:this={fileInput}
    onchange={handleFileChange}
  />

  <!-- svelte-ignore a11y_click_events_have_key_events -->
  <!-- svelte-ignore a11y_no_static_element_interactions -->
  <div
    class="mt-2 block w-full cursor-pointer rounded-lg border-2 border-dashed border-gray-300 bg-white p-5
          text-center transition hover:border-indigo-400
          hover:bg-gray-50 sm:p-6
          dark:border-gray-700 dark:bg-neutral-900 dark:hover:bg-neutral-800
          {isDragOver
      ? 'border-indigo-400 bg-indigo-50 dark:border-indigo-700 dark:bg-indigo-900/20'
      : ''}"
    aria-label="Upload files"
    onclick={handleContainerClick}
    ondragenter={handleDragEnter}
    ondragover={handleDragOver}
    ondragleave={handleDragLeave}
    ondrop={handleDrop}
  >
    {#if files.length > 0}
      <div class="space-y-3 text-left">
        {#each files as file, index (file)}
          <FileAttachmentItem
            {file}
            {index}
            name={fileNames[index] ?? ''}
            description={fileDescriptions[index] ?? ''}
            namePlaceholder={fileNamePlaceholder}
            descriptionPlaceholder={fileDescriptionPlaceholder}
            selectedText={selectedFileText}
            removeText={removeFileText}
            {showRemoveButton}
            onNameChange={updateFileName}
            onDescriptionChange={updateFileDesc}
            onRemove={handleRemoveFile}
          />
        {/each}
      </div>
    {:else}
      <div class="pointer-events-none space-y-2">
        <svg
          class="mx-auto h-12 w-12 text-gray-400"
          stroke="currentColor"
          fill="none"
          viewBox="0 0 48 48"
          aria-hidden="true"
        >
          <path
            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        <div>
          <p class="text-sm text-gray-600 dark:text-gray-300">
            <span class="font-medium text-indigo-600 dark:text-indigo-400">{uploadText}</span>
            {dropText}
          </p>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{placeholder}</p>
        </div>
      </div>
    {/if}
  </div>

  {#if errorMsg}
    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{errorMsg}</p>
  {/if}

  {#if files.length > 0}
    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
      <span
        class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs
                    font-medium text-green-800 dark:bg-green-900 dark:text-green-200"
      >
        <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13l4 4L19 7"
          />
        </svg>
        {files.length}/{maxFiles}
        {fileCountText}
      </span>
      <span class="text-xs text-gray-500 dark:text-gray-400"
        >{totalText}: {formatFileSize(totalSize)}</span
      >
    </div>
  {/if}
</div>

<style>
  :global(.break-all) {
    word-break: break-all;
    overflow-wrap: anywhere;
  }
</style>
