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

export function dedupeFiles(files: File[]): File[] {
  const seen = new Set<string>();
  return files.filter((file) => {
    const signature = `${file.name}|${file.size}|${file.lastModified}`;
    if (seen.has(signature)) return false;
    seen.add(signature);
    return true;
  });
}

export function validateFileSizes(
  files: File[],
  maxSizeBytes: number
): { ok: boolean; reason?: string } {
  const invalidFile = files.find((file) => maxSizeBytes && file.size > maxSizeBytes);
  return invalidFile
    ? {
        ok: false,
        reason: `Ukuran "${invalidFile.name}" melebihi ${formatFileSize(maxSizeBytes)}.`
      }
    : { ok: true };
}

export function shortenFileName(name: string, max = 36): string {
  if (!name || name.length <= max) return name;
  const head = Math.ceil((max - 1) / 2);
  const tail = Math.floor((max - 1) / 2);
  return `${name.slice(0, head)}...${name.slice(-tail)}`;
}

export function getFileIcon(fileName: string): string {
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
