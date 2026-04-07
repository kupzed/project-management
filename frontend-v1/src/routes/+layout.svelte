<script lang="ts">
  import '../app.css'; // Pastikan Tailwind CSS diimpor
  import axiosClient from '$lib/axiosClient';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { onMount } from 'svelte';
  import { theme } from '$lib/stores/theme';
  import { setPermissions, setRoles } from '$lib/stores/permissions';
  import { setUser, currentUser } from '$lib/stores/user';

  onMount(() => {
    // Berlangganan tanpa menggunakan nilainya; ini memastikan efek samping dijalankan
    const unsubscribe = theme.subscribe(() => {});
    return unsubscribe;
  });

  // Import komponen layout
  import Sidebar from '$lib/components/layout/Sidebar.svelte';
  import TopNav from '$lib/components/layout/TopNav.svelte';
  import MobileSidebar from '$lib/components/layout/MobileSidebar.svelte';
  import { browser } from '$app/environment';

  // State sidebar
  let sidebarOpen: boolean = false;       // Untuk mobile sidebar
  let sidebarCollapsed: boolean = false;  // Untuk desktop sidebar (collapse/expand)

  // Fungsi logout
  async function logout() {
    if (confirm('Apakah Anda yakin ingin logout?')) {
      try {
        await axiosClient.post('/auth/logout');
        localStorage.removeItem('jwt_token');
        alert('Logout berhasil!');
        goto('/auth/login');
      } catch (error) {
        console.error('Logout failed:', error);
        localStorage.removeItem('jwt_token');
        alert('Logout gagal, namun Anda telah keluar dari sesi.');
        goto('/auth/login');
      }
    }
  }

  // Cek apakah halaman saat ini adalah rute auth
  $: isAuthRoute = $page.url.pathname.startsWith('/auth');

  // Load user data dari server
  async function loadUserData() {
    if (!browser) return;
    if (isAuthRoute || !localStorage.getItem('jwt_token')) return;
    
    // Jangan fetch ulang jika data sudah ada di store
    if ($currentUser) return;

    try {
      const res = await axiosClient.get('/auth/me');
      if (res.status === 200) {
        const data = res.data?.data ?? res.data;
        // Update user info
        setUser({
          id: data.id,
          name: data.name,
          email: data.email
        });
        // Update roles & permissions
        setRoles(data.roles ?? []);
        setPermissions(data.permissions ?? []);
      }
    } catch (err) {
      console.error('Failed to fetch user data:', err);
    }
  }

  // Re-run setiap kali navigasi ke rute non-auth atau ketika isAuthRoute berubah
  $: if (browser && !isAuthRoute && $page.url.pathname) {
    loadUserData();
  }

  // Fungsi untuk mendapatkan title berdasarkan route
  function getPageTitle(pathname: string): string {
    switch (pathname) {
      case '/dashboard': return 'Dashboard';
      case '/projects': return 'Daftar Project';
      case '/activities': return 'Daftar Activity';
      case '/mitras': return 'Daftar Mitra';
      case '/settings': return 'Pengaturan';
      case '/barang-certificates': return 'Daftar Barang Sertifikat';
      case '/certificates': return 'Daftar Sertifikat';
      default:
        if (pathname.startsWith('/projects/')) return 'Detail Project';
        if (pathname.startsWith('/activities/')) return 'Detail Activity';
        if (pathname.startsWith('/mitras/')) return 'Detail Mitra';
        if (pathname.startsWith('/settings/')) return 'Pengaturan';
        if (pathname.startsWith('/barang-certificates/')) return 'Detail Barang Sertifikat';
        if (pathname.startsWith('/certificates/')) return 'Detail Sertifikat';
        return 'Dashboard';
    }
  }
</script>

{#if isAuthRoute}
  <slot></slot>
{:else}
  <div class="flex h-screen bg-gray-100 font-sans">
    <Sidebar
      bind:collapsed={sidebarCollapsed}
      on:toggleCollapsed={() => (sidebarCollapsed = !sidebarCollapsed)}
      on:logout={logout}
    />

    <div
      class="flex-1 flex flex-col overflow-hidden transition-all duration-300 ease-in-out"
      class:lg:pl-64={!sidebarCollapsed}
      class:lg:pl-20={sidebarCollapsed}
    >
      <MobileSidebar
        bind:open={sidebarOpen}
        on:close={() => (sidebarOpen = false)}
        on:logout={logout}
      />

      <TopNav
        on:toggleMobileSidebar={() => (sidebarOpen = true)}
        on:logout={logout}
      >
        <svelte:fragment slot="topnav-title">
          <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            {getPageTitle($page.url.pathname)}
          </h1>
        </svelte:fragment>
      </TopNav>

      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-neutral-800 p-6">
        <div class="mx-auto mb-16">
          <slot></slot>
        </div>
      </main>
    </div>
  </div>
{/if}

<style>
  /* Tidak perlu style tambahan jika menggunakan Tailwind */
  /* Pastikan src/app.css hanya berisi import Tailwind + custom CSS global jika ada */
</style>