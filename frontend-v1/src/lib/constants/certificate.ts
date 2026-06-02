import type { CertificateStatus } from '$lib/types/certificate';

type CertificateStatusConfig = {
	label: string;
	bgLight: string;
	bgDark: string;
	textLight: string;
	textDark: string;
	icon: string;
};

export const CERTIFICATE_STATUS_CONFIG = {
	Aktif: {
		label: 'Aktif',
		bgLight: 'bg-green-100',
		bgDark: 'dark:bg-green-900',
		textLight: 'text-green-800',
		textDark: 'dark:text-green-200',
		icon: 'badge-check'
	},
	'Tidak Aktif': {
		label: 'Tidak Aktif',
		bgLight: 'bg-red-100',
		bgDark: 'dark:bg-red-900',
		textLight: 'text-red-800',
		textDark: 'dark:text-red-200',
		icon: 'badge-x'
	},
	Belum: {
		label: 'Belum',
		bgLight: 'bg-yellow-100',
		bgDark: 'dark:bg-yellow-900',
		textLight: 'text-yellow-800',
		textDark: 'dark:text-yellow-200',
		icon: 'clock'
	}
} as const satisfies Record<CertificateStatus, CertificateStatusConfig>;

export const CERTIFICATE_STATUS_OPTIONS = [
	'Belum',
	'Tidak Aktif',
	'Aktif'
] as const satisfies readonly CertificateStatus[];

export const CERTIFICATE_SORT_FIELDS = ['created', 'date_of_issue', 'date_of_expired'] as const;
