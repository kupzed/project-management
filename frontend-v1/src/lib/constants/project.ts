import type { ProjectKategori, ProjectStatus } from '$lib/types/project';

type StatusConfig = {
	label: string;
	bgLight: string;
	bgDark: string;
	textLight: string;
	textDark: string;
	icon: string;
};

export const PROJECT_STATUS_CONFIG = {
	Complete: {
		label: 'Complete',
		bgLight: 'bg-green-100',
		bgDark: 'dark:bg-green-900',
		textLight: 'text-green-800',
		textDark: 'dark:text-green-200',
		icon: 'check-circle'
	},
	Ongoing: {
		label: 'Ongoing',
		bgLight: 'bg-blue-100',
		bgDark: 'dark:bg-blue-900',
		textLight: 'text-blue-800',
		textDark: 'dark:text-blue-200',
		icon: 'loader-circle'
	},
	Prospect: {
		label: 'Prospect',
		bgLight: 'bg-yellow-100',
		bgDark: 'dark:bg-yellow-900',
		textLight: 'text-yellow-800',
		textDark: 'dark:text-yellow-200',
		icon: 'search'
	},
	Cancel: {
		label: 'Cancel',
		bgLight: 'bg-red-100',
		bgDark: 'dark:bg-red-900',
		textLight: 'text-red-800',
		textDark: 'dark:text-red-200',
		icon: 'x-circle'
	}
} as const satisfies Record<ProjectStatus, StatusConfig>;

export const PROJECT_STATUS_OPTIONS = [
	'Ongoing',
	'Prospect',
	'Complete',
	'Cancel'
] as const satisfies readonly ProjectStatus[];

export const PROJECT_KATEGORI_OPTIONS = [
	'PLTS Hybrid',
	'PLTS Ongrid',
	'PLTS Offgrid',
	'PJUTS All In One',
	'PJUTS Two In One',
	'PJUTS Konvensional'
] as const satisfies readonly ProjectKategori[];

export const PER_PAGE_OPTIONS = [10, 25, 50, 100] as const;
export const DEFAULT_PER_PAGE = 50;

export const FINANCE_CATEGORIES = [
	'Expense Report',
	'Invoice',
	'Invoice & FP',
	'Payment',
	'Faktur Pajak',
	'Kasbon'
] as const;
