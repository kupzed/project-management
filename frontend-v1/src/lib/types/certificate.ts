import type { Attachment, ExistingAttachment } from './attachment';
import type { Option, PaginatedResponse, SortOrder } from './common';
import type { BarangCertificateSummary } from './barang-certificate';
import type { ProjectSummary } from './project';

export type CertificateStatus = 'Aktif' | 'Tidak Aktif' | 'Belum';

export type CertificateSortBy = 'created' | 'date_of_issue' | 'date_of_expired';

/**
 * Certificate API entity from CertificateResource.
 * Nested `project`, `barang_certificate`, and `attachments` mirror eager-loaded
 * Laravel relations used by the certificate service.
 */
export type Certificate = {
	id: number;
	name: string;
	no_certificate: string;
	project_id: number | null;
	barang_certificate_id: number | null;
	status: CertificateStatus;
	date_of_issue: string | null;
	date_of_expired: string | null;
	created_at: string | null;
	updated_at: string | null;
	project?: ProjectSummary | null;
	barang_certificate?: BarangCertificateSummary | null;
	attachments?: Attachment[];
};

/**
 * Create form shape sent as multipart/form-data to CertificateRequest.
 * File metadata arrays are index-aligned with `attachments`.
 */
export type CertificateForm = {
	name: string;
	no_certificate: string;
	project_id: number | '' | null;
	barang_certificate_id: number | '' | null;
	status: CertificateStatus | '';
	date_of_issue?: string | null;
	date_of_expired?: string | null;
	attachments: File[];
	attachment_names: string[];
	attachment_descriptions: Array<string | null>;
};

export type CertificateEditForm = CertificateForm & {
	existing_attachments: ExistingAttachment[];
	removed_existing_ids: number[];
};

export type CertificateFilterParams = {
	search?: string;
	status?: CertificateStatus | '';
	project_id?: number | string;
	barang_certificate_id?: number | string;
	date_from?: string;
	date_to?: string;
	page?: number;
	per_page?: number;
	sort_by?: CertificateSortBy;
	sort_dir?: SortOrder;
};

export type CertificateFormDependencies = {
	projects: ProjectSummary[];
	barang_certificates: BarangCertificateSummary[];
	statuses: CertificateStatus[];
	barang_options: Option[];
};

export type CertificatePaginatedResponse = Omit<
	PaginatedResponse<Certificate>,
	'form_dependencies'
> & {
	form_dependencies?: CertificateFormDependencies;
};
