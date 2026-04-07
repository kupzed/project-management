// src/routes/+page.ts
import { redirect } from '@sveltejs/kit';

export const ssr = false;
export const prerender = false;

export async function load() {
	const token = localStorage.getItem('jwt_token');

	// Jika sudah login (ada token) -> ke dashboard
	if (token) {
		return redirect(302, '/dashboard');
	}

	// Jika belum login -> ke halaman login
	return redirect(302, '/auth/login');
}
