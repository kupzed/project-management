import type { Attachment, ExistingAttachment } from './attachment';
import type { Option, PaginatedResponse, SortOrder } from './common';
import type { Mitra } from './mitra';
import type { ProjectSummary } from './project';

export type ActivityJenis = 'Customer' | 'Vendor' | 'Internal';

export type ActivityKategori =
  | 'Expense Report'
  | 'Invoice'
  | 'Invoice & FP'
  | 'Purchase Order'
  | 'Payment'
  | 'Quotation'
  | 'Faktur Pajak'
  | 'Kasbon'
  | 'Laporan Teknis'
  | 'Surat Masuk'
  | 'Surat Keluar'
  | 'Kontrak'
  | 'Berita Acara'
  | 'Receive Item'
  | 'Delivery Order'
  | 'Legalitas'
  | 'Other';

export type ActivitySortBy = 'created' | 'activity_date';

/**
 * Activity API entity from ActivityResource.
 * `project`, `mitra`, and `attachments` are loaded on list/detail endpoints,
 * but are typed as optional to match Laravel conditional serialization.
 */
export type Activity = {
  id: number;
  name: string;
  project_id: number;
  jenis: ActivityJenis;
  mitra_id: number | null;
  kategori: ActivityKategori;
  from: string | null;
  to: string | null;
  short_desc: string | null;
  description: string;
  value: string;
  activity_date: string | null;
  created_at: string | null;
  updated_at: string | null;
  project?: ProjectSummary | null;
  mitra?: Mitra | null;
  attachments?: Attachment[];
};

/**
 * Create form shape sent as multipart/form-data to ActivityRequest.
 * File metadata arrays are index-aligned with `attachments`.
 */
export type ActivityForm = {
  action?: string | null;
  name: string;
  short_desc?: string | null;
  description: string;
  project_id: number | '';
  kategori: ActivityKategori | '';
  value?: number | string | null;
  activity_date: string;
  jenis: ActivityJenis | '';
  mitra_id?: number | string | '' | null;
  from?: string | null;
  to?: string | null;
  attachments: File[];
  attachment_names: string[];
  attachment_descriptions: Array<string | null>;
};

/**
 * Edit form shape for preserving, renaming, and removing existing attachments.
 * Existing attachment rows are translated into `existing_attachment_*` arrays
 * before submission to the Laravel update endpoint.
 */
export type ActivityEditForm = ActivityForm & {
  existing_attachments: ExistingAttachment[];
  removed_existing_ids: number[];
};

export type ActivityFilterParams = {
  search?: string;
  project_id?: number | string;
  jenis?: ActivityJenis | '';
  kategori?: ActivityKategori | '';
  mitra_id?: number | string;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
  sort_by?: ActivitySortBy;
  sort_dir?: SortOrder;
};

export type ActivityFormDependencies = {
  projects: Array<ProjectSummary & { mitra_id: number | null }>;
  customers: Option[];
  vendors: Option[];
  kategori_list: ActivityKategori[];
  jenis_list: ActivityJenis[];
};

export type ActivityPaginatedResponse = Omit<PaginatedResponse<Activity>, 'form_dependencies'> & {
  vendor_options?: Option[];
  form_dependencies?: ActivityFormDependencies;
};
