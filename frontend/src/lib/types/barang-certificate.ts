import type { SortOrder } from './common';
import type { MitraSummary } from './mitra';
import type { Certificate } from './certificate';

export type BarangCertificateSummary = {
  id: number;
  name: string;
  no_seri?: string;
};

/**
 * Barang certificate API entity from BarangCertificateResource.
 * Detail endpoints may include nested certificates; list/store/update include
 * a smaller mitra relation depending on loaded relations.
 */
export type BarangCertificate = {
  id: number;
  name: string;
  no_seri: string;
  mitra_id: number | null;
  created_at: string | null;
  updated_at: string | null;
  mitra?: MitraSummary | null;
  certificates?: Certificate[];
};

export type BarangCertificateForm = {
  name: string;
  no_seri: string;
  mitra_id: number | '';
};

export type BarangCertificateFilterParams = {
  search?: string;
  mitra_id?: number | string;
  page?: number;
  per_page?: number;
  sort_by?: 'created';
  sort_dir?: SortOrder;
};

export type BarangCertificateFormDependencies = {
  mitras: MitraSummary[];
};
