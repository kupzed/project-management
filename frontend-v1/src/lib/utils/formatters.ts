export type DateFormat = 'short' | 'long';

const DATE_OPTIONS: Record<DateFormat, Intl.DateTimeFormatOptions> = {
  short: {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  },
  long: {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  }
};

/** Formats API date strings with the Indonesian locale. */
export function formatDate(dateStr: string, format: DateFormat = 'short'): string {
  if (!dateStr) {
    return '';
  }

  const date = new Date(dateStr);
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return date.toLocaleDateString('id-ID', DATE_OPTIONS[format]);
}

/** Formats numeric values as Indonesian Rupiah. */
export function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(amount);
}

/** Formats a byte count into the same labels used by attachment inputs. */
export function formatFileSize(bytes: number): string {
  if (!bytes) {
    return '';
  }

  const unit = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const index = Math.min(sizes.length - 1, Math.floor(Math.log(bytes) / Math.log(unit)));
  const value = bytes / Math.pow(unit, index);

  return `${Number(value.toFixed(2))} ${sizes[index]}`;
}
