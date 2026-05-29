<script lang="ts">
	import '../app.css';
	import { browser } from '$app/environment';
	import { goto } from '$app/navigation';
	import { page } from '$app/stores';
	import { onMount } from 'svelte';
	import axiosClient from '$lib/axiosClient';
	import MobileSidebar from '$lib/components/layout/MobileSidebar.svelte';
	import Sidebar from '$lib/components/layout/Sidebar.svelte';
	import TopNav from '$lib/components/layout/TopNav.svelte';
	import { getPageTitle } from '$lib/components/layout/nav';
	import { setPermissions, setRoles } from '$lib/stores/permissions';
	import { theme } from '$lib/stores/theme';
	import { currentUser, setUser } from '$lib/stores/user';

	let sidebarOpen = false;
	let sidebarCollapsed = false;

	onMount(() => {
		const unsubscribe = theme.subscribe(() => {});
		return unsubscribe;
	});

	$: isAuthRoute = $page.url.pathname.startsWith('/auth');
	$: pageTitle = getPageTitle($page.url.pathname);
	$: shellOffset = sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64';

	async function logout() {
		if (!confirm('Apakah Anda yakin ingin logout?')) return;

		try {
			await axiosClient.post('/auth/logout');
			localStorage.removeItem('jwt_token');
			setUser(null);
			setRoles([]);
			setPermissions([]);
			alert('Logout berhasil!');
			goto('/auth/login');
		} catch (error) {
			console.error('Logout failed:', error);
			localStorage.removeItem('jwt_token');
			setUser(null);
			setRoles([]);
			setPermissions([]);
			alert('Logout gagal, namun Anda telah keluar dari sesi.');
			goto('/auth/login');
		}
	}

	async function loadUserData() {
		if (!browser) return;
		if (isAuthRoute || !localStorage.getItem('jwt_token')) return;
		if ($currentUser) return;

		try {
			const res = await axiosClient.get('/auth/me');
			if (res.status === 200) {
				const data = res.data?.data ?? res.data;
				setUser({
					id: data.id,
					name: data.name,
					email: data.email
				});
				setRoles(data.roles ?? []);
				setPermissions(data.permissions ?? []);
			}
		} catch (err) {
			console.error('Failed to fetch user data:', err);
		}
	}

	$: if (browser && !isAuthRoute && $page.url.pathname) {
		loadUserData();
	}
</script>

{#if isAuthRoute}
	<slot></slot>
{:else}
	<div class="min-h-svh bg-background font-sans text-foreground">
		<Sidebar
			bind:collapsed={sidebarCollapsed}
			on:toggleCollapsed={() => (sidebarCollapsed = !sidebarCollapsed)}
			on:logout={logout}
		/>

		<div
			class={`flex min-h-svh flex-col transition-[padding] duration-200 ease-linear ${shellOffset}`}
		>
			<MobileSidebar
				bind:open={sidebarOpen}
				on:close={() => (sidebarOpen = false)}
				on:logout={logout}
			/>

			<TopNav on:toggleMobileSidebar={() => (sidebarOpen = true)}>
				<svelte:fragment slot="topnav-title">
					<p class="truncate text-xs font-medium text-muted-foreground">Project Management</p>
					<h1
						class="truncate text-xl leading-7 font-semibold tracking-normal text-foreground md:text-2xl"
					>
						{pageTitle}
					</h1>
				</svelte:fragment>
			</TopNav>

			<main
				class="flex-1 overflow-x-clip bg-background px-4 py-4 pb-[calc(1rem+env(safe-area-inset-bottom))] md:px-6 md:py-6"
			>
				<div class="max-w-1xl mx-auto flex w-full flex-col gap-4">
					<slot></slot>
				</div>
			</main>
		</div>
	</div>
{/if}
