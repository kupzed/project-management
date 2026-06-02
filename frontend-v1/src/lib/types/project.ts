import type { Activity } from './activity';
import type { MitraContactSummary, MitraSummary } from './mitra';
import type { SortOrder } from './common';

export type ProjectStatus = 'Complete' | 'Ongoing' | 'Prospect' | 'Cancel';

export type ProjectKategori =
	| 'PLTS Hybrid'
	| 'PLTS Ongrid'
	| 'PLTS Offgrid'
	| 'PJUTS All In One'
	| 'PJUTS Two In One'
	| 'PJUTS Konvensional';

export type ProjectSortBy = 'created' | 'start_date';

export type ProjectSummary = {
	id: number;
	name: string;
	mitra_id?: number | null;
};

/**
 * Project API entity from ProjectResource.
 * Relation fields are optional because Laravel `whenLoaded` / permission-gated
 * fields can be omitted depending on endpoint and user permissions.
 */
export type Project = {
	id: number;
	name: string;
	mitra_id: number | null;
	mitra?: MitraContactSummary | null;
	kategori: ProjectKategori;
	lokasi: string | null;
	status: ProjectStatus;
	no_po: string | null;
	no_so: string | null;
	description: string;
	start_date: string;
	finish_date: string | null;
	is_cert_projects: boolean;
	cert_projects_label: 'Certificate Project' | 'Regular Project';
	activities_count?: number;
	activities?: Activity[];
	certificates_count?: number;
	created_at: string | null;
	updated_at: string | null;
};

export type ProjectForm = {
	name: string;
	description: string;
	status: ProjectStatus | '';
	start_date: string;
	finish_date?: string | null;
	mitra_id: number | '';
	kategori: ProjectKategori | '';
	lokasi?: string | null;
	no_po?: string | null;
	no_so?: string | null;
	is_cert_projects: boolean;
};

export type ProjectFilterParams = {
	search?: string;
	status?: ProjectStatus | '';
	kategori?: ProjectKategori | '';
	customer_id?: number | string;
	is_cert_projects?: boolean | 0 | 1 | '0' | '1';
	date_from?: string;
	date_to?: string;
	page?: number;
	per_page?: number;
	sort_by?: ProjectSortBy;
	sort_dir?: SortOrder;
};

export type ProjectFormDependencies = {
	customers: MitraSummary[];
	project_status_list: ProjectStatus[];
	project_kategori_list: ProjectKategori[];
};
