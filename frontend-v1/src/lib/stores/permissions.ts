import { writable } from 'svelte/store';

export const userPermissions = writable<string[]>([]);
export const userRoles = writable<string[]>([]);

export function setPermissions(perms: string[]): void {
	userPermissions.set(perms ?? []);
}
export function setRoles(roles: string[]): void {
	userRoles.set(roles ?? []);
}
