import type { ActivityJenis, ActivityKategori } from '$lib/types/activity';

export const ACTIVITY_JENIS_CONFIG = {
	Internal: {
		label: 'Internal',
		bgLight: 'bg-slate-100',
		bgDark: 'dark:bg-slate-800',
		textLight: 'text-slate-800',
		textDark: 'dark:text-slate-100',
		icon: 'building-2'
	},
	Customer: {
		label: 'Customer',
		bgLight: 'bg-blue-100',
		bgDark: 'dark:bg-blue-900',
		textLight: 'text-blue-800',
		textDark: 'dark:text-blue-200',
		icon: 'users'
	},
	Vendor: {
		label: 'Vendor',
		bgLight: 'bg-emerald-100',
		bgDark: 'dark:bg-emerald-900',
		textLight: 'text-emerald-800',
		textDark: 'dark:text-emerald-200',
		icon: 'truck'
	}
} as const satisfies Record<
	ActivityJenis,
	{
		label: string;
		bgLight: string;
		bgDark: string;
		textLight: string;
		textDark: string;
		icon: string;
	}
>;

export const ACTIVITY_JENIS_OPTIONS = [
	'Internal',
	'Customer',
	'Vendor'
] as const satisfies readonly ActivityJenis[];

export const ACTIVITY_CATEGORY_OPTIONS = [
	'Expense Report',
	'Invoice',
	'Invoice & FP',
	'Purchase Order',
	'Payment',
	'Quotation',
	'Faktur Pajak',
	'Kasbon',
	'Laporan Teknis',
	'Surat Masuk',
	'Surat Keluar',
	'Kontrak',
	'Berita Acara',
	'Receive Item',
	'Delivery Order',
	'Legalitas',
	'Other'
] as const satisfies readonly ActivityKategori[];

export const ACTIVITY_FINANCE_CATEGORIES = [
	'Expense Report',
	'Invoice',
	'Invoice & FP',
	'Payment',
	'Faktur Pajak',
	'Kasbon'
] as const satisfies readonly ActivityKategori[];
