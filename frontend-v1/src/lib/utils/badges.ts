import { CERTIFICATE_STATUS_CONFIG, PROJECT_STATUS_CONFIG } from '$lib/constants';
import type { CertificateStatus, ProjectStatus } from '$lib/types';

type BadgeConfig = {
	bgLight: string;
	bgDark: string;
	textLight: string;
	textDark: string;
};

const FALLBACK_BADGE_CLASSES = 'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200';

/** Checks object ownership without depending on prototype shape. */
function hasOwn<T extends object>(value: T, key: PropertyKey): key is keyof T {
	return Object.prototype.hasOwnProperty.call(value, key);
}

/** Joins badge color tokens from a status config entry. */
function composeBadgeClasses(config: BadgeConfig): string {
	return [config.bgLight, config.bgDark, config.textLight, config.textDark].join(' ');
}

/** Resolves a status into badge classes with a neutral fallback. */
function getBadgeClasses<T extends object>(config: T, status: string): string {
	if (!hasOwn(config, status)) {
		return FALLBACK_BADGE_CLASSES;
	}

	return composeBadgeClasses(config[status] as BadgeConfig);
}

/** Returns Tailwind classes for project status badges. */
export function getStatusBadgeClasses(status: ProjectStatus): string;
export function getStatusBadgeClasses(status: string): string;
export function getStatusBadgeClasses(status: string): string {
	return getBadgeClasses(PROJECT_STATUS_CONFIG, status);
}

/** Returns Tailwind classes for certificate status badges. */
export function getCertificateStatusBadgeClasses(status: CertificateStatus): string;
export function getCertificateStatusBadgeClasses(status: string): string;
export function getCertificateStatusBadgeClasses(status: string): string {
	return getBadgeClasses(CERTIFICATE_STATUS_CONFIG, status);
}
