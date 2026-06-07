import type { MitraSummary, ProjectForm } from '$lib/types';

export type View = 'table' | 'list';

export function defaultProjectForm(): ProjectForm {
  return {
    name: '',
    description: '',
    status: '',
    start_date: '',
    finish_date: '',
    mitra_id: '',
    kategori: '',
    lokasi: '',
    no_po: '',
    no_so: '',
    is_cert_projects: false
  };
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null;
}

export function isMitraSummaryArray(value: unknown): value is MitraSummary[] {
  return (
    Array.isArray(value) &&
    value.every((item) => {
      if (!isRecord(item)) return false;
      return typeof item.id === 'number' && typeof item.nama === 'string';
    })
  );
}

export function toStringList<T extends string>(value: unknown): T[] {
  return Array.isArray(value) ? value.filter((item): item is T => typeof item === 'string') : [];
}
