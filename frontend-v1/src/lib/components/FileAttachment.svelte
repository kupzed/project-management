<script lang="ts">
  import { formatFileSize } from '$lib/utils/formatters';

  export type FileAttachmentPayload = {
    files: File[];
    fileNames: string[];
    fileDescriptions: string[];
  };

  export type FileAttachmentRemovePayload = FileAttachmentPayload & {
    index: number;
  };

  export type FileAttachmentNamePayload = {
    index: number;
    name: string;
  };

  export type FileAttachmentDescriptionPayload = {
    index: number;
    description: string;
  };

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

  function dedupeBySignature(arr: File[]): File[] {
    const seen = new Set<string>();
    const out: File[] = [];

    for (const file of arr) {
      const signature = `${file.name}|${file.size}|${file.lastModified}`;
      if (!seen.has(signature)) {
        seen.add(signature);
        out.push(file);
      }
    }

    return out;
  }

  function validateFiles(arr: File[]): { ok: boolean; reason?: string } {
    for (const file of arr) {
      if (maxSizeBytes && file.size > maxSizeBytes) {
        return {
          ok: false,
          reason: `Ukuran "${file.name}" melebihi ${formatFileSize(maxSizeBytes)}.`
        };
      }
    }

    return { ok: true };
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
    const combined = dedupeBySignature([...files, ...newFiles]);
    const limited = combined.slice(0, maxFiles);
    const { ok, reason } = validateFiles(limited);

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

  function shortenMiddle(name: string, max = 36): string {
    if (!name || name.length <= max) {
      return name;
    }

    const head = Math.ceil((max - 1) / 2);
    const tail = Math.floor((max - 1) / 2);
    return `${name.slice(0, head)}...${name.slice(-tail)}`;
  }

  function getFileIcon(fileName: string): string {
    const ext = fileName.split('.').pop()?.toLowerCase();
    switch (ext) {
      case 'pdf':
        return '\u{1F4C4}';
      case 'jpg':
      case 'jpeg':
      case 'png':
      case 'gif':
      case 'webp':
      case 'svg':
        return '\u{1F5BC}\uFE0F';
      case 'doc':
      case 'docx':
        return '\u{1F4DD}';
      case 'xls':
      case 'xlsx':
        return '\u{1F4CA}';
      case 'txt':
        return '\u{1F4C3}';
      case 'mp4':
      case 'avi':
      case 'mov':
      case 'wmv':
      case 'flv':
      case 'webm':
        return '\u{1F3A5}';
      case 'mp3':
      case 'wav':
      case 'ogg':
      case 'm4a':
        return '\u{1F3B5}';
      default:
        return '\u{1F4CE}';
    }
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
        {#each files as file, index}
          <div class="flex items-start gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
            <span class="shrink-0 text-xl">{getFileIcon(file.name)}</span>
            <div class="min-w-0 flex-1 space-y-2">
              <input
                type="text"
                value={fileNames[index] ?? ''}
                required
                onclick={(event) => event.stopPropagation()}
                onmousedown={(event) => event.stopPropagation()}
                oninput={(event) => updateFileName(index, event.currentTarget.value)}
                placeholder={fileNamePlaceholder}
                class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-sm
                      text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500
                      dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
              <input
                type="text"
                value={fileDescriptions[index] ?? ''}
                onclick={(event) => event.stopPropagation()}
                onmousedown={(event) => event.stopPropagation()}
                oninput={(event) => updateFileDesc(index, event.currentTarget.value)}
                placeholder={fileDescriptionPlaceholder}
                class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-sm
                      text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500
                      dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
              <div
                class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400"
              >
                <span title={file.name} class="truncate">{shortenMiddle(file.name)}</span>
                <span class="ml-2">{formatFileSize(file.size)}</span>
                <span class="ml-auto flex items-center gap-1">
                  <svg
                    class="h-3 w-3 text-green-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M5 13l4 4L19 7"
                    />
                  </svg>
                  {selectedFileText}
                </span>
              </div>
            </div>
            {#if showRemoveButton}
              <button
                type="button"
                onclick={(event) => {
                  event.stopPropagation();
                  handleRemoveFile(index);
                }}
                class="shrink-0 rounded p-1 text-red-600 transition hover:bg-red-50
                      hover:text-red-800 dark:text-red-400 dark:hover:bg-red-900/20 dark:hover:text-red-300"
                aria-label={`${removeFileText} ${file.name}`}
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
