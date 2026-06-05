<script lang="ts">
  import { onMount } from 'svelte';
  import {
    fetchRoleConfig,
    fetchRoleUsers,
    updatePassword,
    updateProfile,
    updateRole,
    type ActionConfig,
    type ModuleConfig,
    type ModulesState,
    type RoleForm,
    type RoleUser
  } from '$lib/services/settingsService';
  import { userRoles } from '$lib/stores/permissions';
  import { currentUser, patchUser } from '$lib/stores/user';
  import { extractApiErrors } from '$lib/utils/errors';
  import { showError, showSuccess } from '$lib/utils/toast';

  // ---------------------------
  // Global UI
  // ---------------------------
  let disabled = $state(false);
  let activeTab = $state<'profile' | 'keamanan' | 'role'>('profile');
  let pageLoading = $state(true);
  let errorMsg = $state('');

  // ---------------------------
  // Profile
  // ---------------------------
  let profile = $state({ name: '', email: '' });
  let initialProfile = $state({ name: '', email: '' });
  let profileInitialized = $state(false);

  // Sinkronisasi Profile dari Store secara Reaktif
  $effect(() => {
    if ($currentUser && !profileInitialized) {
      profile.name = $currentUser.name;
      profile.email = $currentUser.email;
      initialProfile = { ...profile };
      profileInitialized = true;
    }
  });

  let savingProfile = $state(false);

  let profileDirty = $derived(JSON.stringify(profile) !== JSON.stringify(initialProfile));

  async function submitProfile() {
    if (!profileDirty || savingProfile) return;
    savingProfile = true;
    errorMsg = '';
    try {
      const data = await updateProfile(profile.name);
      profile.name = data.name ?? profile.name;
      initialProfile = { ...profile };
      patchUser({ name: profile.name });
      showSuccess('Profil berhasil diperbarui');
    } catch (err) {
      showError(extractApiErrors(err));
    } finally {
      savingProfile = false;
    }
  }

  function resetProfileToInitial() {
    profile = { ...initialProfile };
  }

  // ---------------------------
  // Keamanan / Password
  // ---------------------------
  let pw = $state({ current: '', next: '', confirm: '' });
  let showPw = $state({ current: false, next: false, confirm: false });
  let savingPw = $state(false);

  let pwRules = $derived({
    len8: pw.next.length >= 8,
    hasLower: /[a-z]/.test(pw.next),
    hasUpper: /[A-Z]/.test(pw.next),
    notSameAsOld: pw.next !== '' && pw.current !== '' && pw.next !== pw.current,
    confirmMatch: pw.next !== '' && pw.next === pw.confirm
  });
  let canUpdatePw = $derived(
    pwRules.len8 &&
      pwRules.hasLower &&
      pwRules.hasUpper &&
      pwRules.notSameAsOld &&
      pwRules.confirmMatch &&
      !savingPw
  );

  async function handleChangePassword() {
    if (!canUpdatePw) return;
    savingPw = true;
    try {
      await updatePassword(pw);
      showSuccess('Password berhasil diperbarui');
      pw = { current: '', next: '', confirm: '' };
    } catch (err) {
      showError(extractApiErrors(err));
    } finally {
      savingPw = false;
    }
  }

  // ---------------------------
  // Role (DINAMIS DARI BACKEND)
  // ---------------------------
  // Variable state untuk menyimpan config
  let moduleList = $state<ModuleConfig[]>([]);
  let actionList = $state<ActionConfig[]>([]);
  let roleList = $state<{ key: string; label: string }[]>([]);

  // State untuk expand/collapse modul di UI
  let expandedModules = $state<Record<string, boolean>>({});
  let users = $state<RoleUser[]>([]);

  // Sinkronisasi Roles dari Store secara Reaktif
  let myRoles = $derived($userRoles);
  let currentIsOnlyAdmin = $derived(myRoles.includes('admin') && !myRoles.includes('super_admin'));
  let canManageRoles = $derived(myRoles.includes('admin') || myRoles.includes('super_admin'));
  let selectedUserIsSuperAdmin = $state(false);

  let selectedUserId = $state('');

  // Inisialisasi awal kosong, akan di-rebuild saat data config masuk
  let role = $state<RoleForm>({
    userId: '',
    selectedRole: 'user',
    modules: {}
  });
  let initialRole = $state<RoleForm>({
    userId: '',
    selectedRole: 'user',
    modules: {}
  });
  let savingRole = $state(false);

  let roleDirty = $derived(JSON.stringify(role) !== JSON.stringify(initialRole));

  // Helper membuat object kosong berdasarkan moduleList & actionList yang sudah ditarik
  function createEmptyModules(): ModulesState {
    const mods: ModulesState = {};
    if (moduleList.length === 0) return mods;

    for (const m of moduleList) {
      mods[m.key] = {};
      const actions = m.actions ?? actionList;
      for (const a of actions) {
        mods[m.key][a.key] = false;
      }
    }
    return mods;
  }

  function cloneModules(mods: ModulesState): ModulesState {
    return structuredClone(mods);
  }

  function applyUserRole(user: RoleUser | null) {
    // Pastikan config sudah ada sebelum apply role
    if (moduleList.length === 0) return;

    if (!user) {
      selectedUserId = '';
      selectedUserIsSuperAdmin = false;
      const empty = createEmptyModules();
      role = { userId: '', selectedRole: 'user', modules: empty };
      initialRole = { userId: '', selectedRole: 'user', modules: cloneModules(empty) };
      return;
    }

    const perms = (user.permissions ?? []) as string[];
    const modules = createEmptyModules();

    // Map permissions string ("project-view") ke struktur object
    for (const m of moduleList) {
      const actions = m.actions ?? actionList;
      for (const a of actions) {
        const permName = `${m.key}-${a.key}`;
        if (modules[m.key]) {
          modules[m.key][a.key] = perms.includes(permName);
        }
      }
    }

    selectedUserId = String(user.id);
    role = {
      userId: selectedUserId,
      selectedRole: user.roles && user.roles.length > 0 ? user.roles[0] : 'user',
      modules
    };

    selectedUserIsSuperAdmin = user.roles?.includes('super_admin') ?? false;

    initialRole = {
      userId: role.userId,
      selectedRole: role.selectedRole,
      modules: cloneModules(role.modules)
    };
  }

  function buildPermissionsPayload(r: RoleForm): Record<string, boolean> {
    const permissions: Record<string, boolean> = {};

    for (const m of moduleList) {
      const modState = r.modules[m.key];
      if (!modState) continue;

      const actions = m.actions ?? actionList;
      for (const a of actions) {
        permissions[`${m.key}-${a.key}`] = modState[a.key];
      }
    }
    return permissions;
  }

  function toggleModuleAll(moduleKey: string, checked: boolean) {
    // Copy module state
    const updatedModule = { ...role.modules[moduleKey] };

    // Set semua action menjadi checked/unchecked
    const modConfig = moduleList.find((m) => m.key === moduleKey);
    const actions = modConfig?.actions ?? actionList;

    for (const a of actions) {
      updatedModule[a.key] = checked;
    }

    role = {
      ...role,
      modules: {
        ...role.modules,
        [moduleKey]: updatedModule
      }
    };
  }

  function handleToggleAction(moduleKey: string, actionKey: string, checked: boolean) {
    const updatedModule = {
      ...role.modules[moduleKey],
      [actionKey]: checked
    };
    role = {
      ...role,
      modules: {
        ...role.modules,
        [moduleKey]: updatedModule
      }
    };
  }

  function moduleAllChecked(moduleKey: string): boolean {
    const mod = role.modules[moduleKey];
    if (!mod) return false;
    const modConfig = moduleList.find((m) => m.key === moduleKey);
    const actions = modConfig?.actions ?? actionList;
    return actions.every((a) => mod[a.key]);
  }

  function moduleSomeChecked(moduleKey: string): boolean {
    const mod = role.modules[moduleKey];
    if (!mod) return false;
    const modConfig = moduleList.find((m) => m.key === moduleKey);
    const actions = modConfig?.actions ?? actionList;
    const someChecked = actions.some((a) => mod[a.key]);
    const all = actions.every((a) => mod[a.key]);
    return someChecked && !all;
  }

  // Directive Svelte (tidak berubah)
  function indeterminate(node: HTMLInputElement, value: boolean) {
    node.indeterminate = Boolean(value);
    return {
      update(value: boolean) {
        node.indeterminate = Boolean(value);
      }
    };
  }
  function syncChecked(node: HTMLInputElement, value: boolean) {
    node.checked = Boolean(value);
    return {
      update(value: boolean) {
        node.checked = Boolean(value);
      }
    };
  }

  async function submitRole() {
    if (!roleDirty || savingRole) return;
    if (!role.userId) {
      showError('Silakan pilih user terlebih dahulu.');
      return;
    }

    savingRole = true;
    try {
      await updateRole({
        user_id: Number(role.userId),
        permissions: buildPermissionsPayload(role),
        role: role.selectedRole
      });
      initialRole = {
        userId: role.userId,
        selectedRole: role.selectedRole,
        modules: cloneModules(role.modules)
      };
      showSuccess('Role & akses berhasil diperbarui');
    } catch (err) {
      showError(extractApiErrors(err));
    } finally {
      savingRole = false;
    }
  }

  function resetRoleToInitial() {
    role = {
      userId: initialRole.userId,
      selectedRole: initialRole.selectedRole,
      modules: cloneModules(initialRole.modules)
    };
    selectedUserId = role.userId;
  }

  function handleProfileSubmit(event: SubmitEvent) {
    event.preventDefault();
    void submitProfile();
  }

  function handleRoleSubmit(event: SubmitEvent) {
    event.preventDefault();
    void submitRole();
  }

  function handleUserChange(event: Event) {
    const selectedId = Number((event.currentTarget as HTMLSelectElement).value);
    const selected = users.find((u) => u.id === selectedId) ?? null;
    applyUserRole(selected);
  }

  function handleModuleChange(event: Event, moduleKey: string) {
    toggleModuleAll(moduleKey, (event.currentTarget as HTMLInputElement).checked);
  }

  function handleActionChange(event: Event, moduleKey: string, actionKey: string) {
    handleToggleAction(moduleKey, actionKey, (event.currentTarget as HTMLInputElement).checked);
  }

  $effect(() => {
    if (!canManageRoles && activeTab === 'role') {
      activeTab = 'profile';
    }
  });

  // ---------------------------
  // Bootstrap
  // ---------------------------
  onMount(async () => {
    pageLoading = true;
    errorMsg = '';
    try {
      // Data profile dan password tetap di-bootstrap di sini
    } catch (err) {
      console.error(err);
      errorMsg = extractApiErrors(err);
      showError(errorMsg);
    } finally {
      pageLoading = false;
    }
  });

  // Fetch Config & Users secara reaktif jika canManageRoles menjadi true
  let usersLoaded = $state(false);
  let configLoaded = $state(false);
  $effect(() => {
    if (!canManageRoles || pageLoading) return;

    void (async () => {
      // 1. Fetch Config Jika Belum
      if (!configLoaded) {
        try {
          const config = await fetchRoleConfig();
          roleList = config.roles;
          moduleList = config.modules;
          actionList = config.actions;
          moduleList.forEach((m) => (expandedModules[m.key] = true));
          configLoaded = true;
        } catch (e) {
          console.error('Gagal memuat config role:', e);
        }
      }

      // 2. Fetch Users Jika Belum (Hanya setelah config ready)
      if (configLoaded && !usersLoaded) {
        try {
          users = await fetchRoleUsers();
          if (users.length > 0) applyUserRole(users[0]);
          usersLoaded = true;
        } catch (e) {
          console.error('Gagal memuat daftar user:', e);
        }
      }
    })();
  });
</script>

<svelte:head>
  <title>Settings - Indogreen</title>
</svelte:head>

<div class="max-w-1xl">
  {#if pageLoading}
    <div class="mb-4 text-sm text-gray-900 dark:text-white">Memuat data…</div>
  {/if}
  {#if errorMsg}
    <div
      class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-200"
    >
      {errorMsg}
    </div>
  {/if}

  <!-- Tabs -->
  <div class="mb-4 inline-flex rounded-lg bg-gray-200 p-1 dark:bg-gray-700" role="tablist">
    <button
      onclick={() => (activeTab = 'profile')}
      class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-all duration-200 dark:text-gray-200"
      class:bg-white={activeTab === 'profile'}
      class:dark:bg-neutral-900={activeTab === 'profile'}
      class:shadow={activeTab === 'profile'}
      role="tab"
      aria-selected={activeTab === 'profile'}
      aria-controls="panel-profile"
      id="tab-profile"
    >
      Profile
    </button>
    <button
      onclick={() => (activeTab = 'keamanan')}
      class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-all duration-200 dark:text-gray-200"
      class:bg-white={activeTab === 'keamanan'}
      class:dark:bg-neutral-900={activeTab === 'keamanan'}
      class:shadow={activeTab === 'keamanan'}
      role="tab"
      aria-selected={activeTab === 'keamanan'}
      aria-controls="panel-keamanan"
      id="tab-keamanan"
    >
      Keamanan
    </button>

    {#if canManageRoles}
      <button
        onclick={() => (activeTab = 'role')}
        class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-all duration-200 dark:text-gray-200"
        class:bg-white={activeTab === 'role'}
        class:dark:bg-neutral-900={activeTab === 'role'}
        class:shadow={activeTab === 'role'}
        role="tab"
        aria-selected={activeTab === 'role'}
        aria-controls="panel-role"
        id="tab-role"
      >
        Role
      </button>
    {/if}
  </div>

  <!-- PROFILE TAB -->
  {#if activeTab === 'profile'}
    <div id="panel-profile" role="tabpanel" aria-labelledby="tab-profile">
      <form onsubmit={handleProfileSubmit}>
        <div class="space-y-12 rounded-lg bg-white px-4 py-4 sm:px-6 lg:px-8 dark:bg-black">
          <div>
            <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">
              Personal Information
            </h2>
            <p class="mt-1 text-sm/6 text-gray-600 dark:text-gray-300">
              Kelola informasi data pribadi kamu.
            </p>

            <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
              <div class="sm:col-span-4">
                <label
                  for="name"
                  class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">Name</label
                >
                <div class="mt-2">
                  <input
                    id="name"
                    type="text"
                    bind:value={profile.name}
                    autocomplete="given-name"
                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1
                           outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2
                           focus:outline-indigo-600 disabled:opacity-60 sm:text-sm/6 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700"
                    disabled={disabled || pageLoading || savingProfile}
                  />
                </div>
              </div>

              <div class="sm:col-span-4">
                <label
                  for="email"
                  class="block text-sm/6 font-medium text-gray-900 dark:text-gray-300"
                  >Email address</label
                >
                <div class="mt-2">
                  <input
                    id="email"
                    type="email"
                    bind:value={profile.email}
                    autocomplete="email"
                    readonly
                    class="block w-full cursor-not-allowed rounded-md bg-gray-100 px-3 py-1.5 text-base text-gray-700 outline-1
                           -outline-offset-1 outline-gray-300 placeholder:text-gray-400 sm:text-sm/6 dark:bg-neutral-800 dark:text-gray-300 dark:outline-gray-700"
                  />
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                  Email hanya ditampilkan dan tidak bisa diubah.
                </p>
              </div>
            </div>

            <div class="mt-8 flex items-center justify-end gap-3">
              <button
                type="button"
                onclick={resetProfileToInitial}
                class="text-sm/6 font-semibold text-gray-900 dark:text-gray-200"
                disabled={!profileDirty || savingProfile}
              >
                Reset
              </button>
              <button
                type="submit"
                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500
                       focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-60"
                disabled={!profileDirty || savingProfile || pageLoading}
              >
                {savingProfile ? 'Saving…' : 'Save'}
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  {/if}

  <!-- KEAMANAN TAB -->
  {#if activeTab === 'keamanan'}
    <div id="panel-keamanan" role="tabpanel" aria-labelledby="tab-keamanan">
      <div class="rounded-lg bg-white px-4 py-4 sm:px-6 lg:px-8 dark:bg-black">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div class="lg:col-span-2">
            <div>
              <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">Ubah Password</h2>
              <p class="mt-1 text-sm/6 text-gray-600 dark:text-gray-300">
                Ubah Password dengan memasukkan password lama dan password baru
              </p>

              <div class="mt-6 space-y-5">
                <!-- Lama -->
                <div>
                  <span class="block text-sm font-medium text-gray-900 dark:text-gray-100"
                    >Password Lama</span
                  >
                  <div class="relative mt-2">
                    <input
                      type={showPw.current ? 'text' : 'password'}
                      class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1
                             outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600
                             dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700"
                      placeholder="Masukkan password lama"
                      bind:value={pw.current}
                      autocomplete="current-password"
                    />
                    <button
                      type="button"
                      class="absolute inset-y-0 right-2 my-auto rounded-md p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400"
                      onclick={() => (showPw = { ...showPw, current: !showPw.current })}
                      aria-label="Toggle password lama"
                    >
                      {#if showPw.current}
                        <svg
                          viewBox="0 0 24 24"
                          class="size-5"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="1.8"
                        >
                          <path d="M3 3l18 18" />
                          <path
                            d="M10.58 10.58A3 3 0 0 0 9 12a3 3 0 0 0 3 3c.49 0 .95-.12 1.35-.33"
                          />
                          <path
                            d="M6.61 6.61C4.27 7.98 2.73 10 2.73 10s3.64 6.27 9.27 6.27c1.06 0 2.07-.17 3.01-.49"
                          />
                          <path
                            d="M17.94 13.11C19.73 11.86 21.27 10 21.27 10S17.64 3.73 12 3.73a8.8 8.8 0 0 0-2.18.28"
                          />
                        </svg>
                      {:else}
                        <svg
                          viewBox="0 0 24 24"
                          class="size-5"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="1.8"
                        >
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12Z" />
                          <circle cx="12" cy="12" r="3" />
                        </svg>
                      {/if}
                    </button>
                  </div>
                </div>

                <!-- Baru -->
                <div>
                  <span class="block text-sm font-medium text-gray-900 dark:text-gray-100"
                    >Password Baru</span
                  >
                  <div class="relative mt-2">
                    <input
                      type={showPw.next ? 'text' : 'password'}
                      class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1
                             outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600
                             dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700"
                      placeholder="Masukkan password baru"
                      bind:value={pw.next}
                      autocomplete="new-password"
                    />
                    <button
                      type="button"
                      class="absolute inset-y-0 right-2 my-auto rounded-md p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400"
                      onclick={() => (showPw = { ...showPw, next: !showPw.next })}
                      aria-label="Toggle password baru"
                    >
                      {#if showPw.next}
                        <svg
                          viewBox="0 0 24 24"
                          class="size-5"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="1.8"
                        >
                          <path d="M3 3l18 18" />
                          <path
                            d="M10.58 10.58A3 3 0 0 0 9 12a3 3 0 0 0 3 3c.49 0 .95-.12 1.35-.33"
                          />
                          <path
                            d="M6.61 6.61C4.27 7.98 2.73 10 2.73 10s3.64 6.27 9.27 6.27c1.06 0 2.07-.17 3.01-.49"
                          />
                          <path
                            d="M17.94 13.11C19.73 11.86 21.27 10 21.27 10S17.64 3.73 12 3.73a8.8 8.8 0 0 0-2.18.28"
                          />
                        </svg>
                      {:else}
                        <svg
                          viewBox="0 0 24 24"
                          class="size-5"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="1.8"
                        >
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12Z" />
                          <circle cx="12" cy="12" r="3" />
                        </svg>
                      {/if}
                    </button>
                  </div>
                </div>

                <!-- Konfirmasi -->
                <div>
                  <span class="block text-sm font-medium text-gray-900 dark:text-gray-100"
                    >Konfirmasi Password Baru</span
                  >
                  <div class="relative mt-2">
                    <input
                      type={showPw.confirm ? 'text' : 'password'}
                      class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1
                             outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600
                             dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700"
                      placeholder="Masukkan konfirmasi password baru"
                      bind:value={pw.confirm}
                      autocomplete="new-password"
                    />
                    <button
                      type="button"
                      class="absolute inset-y-0 right-2 my-auto rounded-md p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400"
                      onclick={() => (showPw = { ...showPw, confirm: !showPw.confirm })}
                      aria-label="Toggle konfirmasi password"
                    >
                      {#if showPw.confirm}
                        <svg
                          viewBox="0 0 24 24"
                          class="size-5"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="1.8"
                        >
                          <path d="M3 3l18 18" />
                          <path
                            d="M10.58 10.58A3 3 0 0 0 9 12a3 3 0 0 0 3 3c.49 0 .95-.12 1.35-.33"
                          />
                          <path
                            d="M6.61 6.61C4.27 7.98 2.73 10 2.73 10s3.64 6.27 9.27 6.27c1.06 0 2.07-.17 3.01-.49"
                          />
                          <path
                            d="M17.94 13.11C19.73 11.86 21.27 10 21.27 10S17.64 3.73 12 3.73a8.8 8.8 0 0 0-2.18.28"
                          />
                        </svg>
                      {:else}
                        <svg
                          viewBox="0 0 24 24"
                          class="size-5"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="1.8"
                        >
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12Z" />
                          <circle cx="12" cy="12" r="3" />
                        </svg>
                      {/if}
                    </button>
                  </div>
                  {#if pw.confirm && !pwRules.confirmMatch}
                    <p class="mt-2 text-xs text-red-600 dark:text-red-400">
                      Konfirmasi tidak sama.
                    </p>
                  {/if}
                </div>

                <div class="pt-2">
                  <button
                    type="button"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500
                          focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-60"
                    onclick={() => void handleChangePassword()}
                    disabled={!canUpdatePw}
                  >
                    {savingPw ? 'Memperbarui…' : 'Update Password'}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <aside class="lg:col-span-1">
            <div class="rounded-xl bg-teal-600/90 p-5 text-white dark:bg-teal-700">
              <h3 class="text-lg font-semibold">Persyaratan Password</h3>
              <p class="mt-1 text-sm opacity-90">
                Untuk membuat password yang kuat, ikuti aturan berikut:
              </p>
              <ul class="mt-4 space-y-2 text-sm">
                <li>
                  <span class="mt-0.5"
                    >{#if pwRules.len8}✅{:else}•{/if}</span
                  > Minimal 8 karakter
                </li>
                <li>
                  <span class="mt-0.5"
                    >{#if pwRules.hasLower}✅{:else}•{/if}</span
                  > Minimal satu huruf kecil
                </li>
                <li>
                  <span class="mt-0.5"
                    >{#if pwRules.hasUpper}✅{:else}•{/if}</span
                  > Minimal satu huruf besar
                </li>
                <li>
                  <span class="mt-0.5"
                    >{#if pwRules.notSameAsOld}✅{:else}•{/if}</span
                  > Tidak sama dengan password lama
                </li>
                <li>
                  <span class="mt-0.5"
                    >{#if pwRules.confirmMatch}✅{:else}•{/if}</span
                  > Konfirmasi harus sama
                </li>
              </ul>
            </div>
          </aside>
        </div>
      </div>
    </div>
  {/if}

  <!-- ROLE TAB (hanya kalau bisa manage) -->
  {#if activeTab === 'role' && canManageRoles}
    <div id="panel-role" role="tabpanel" aria-labelledby="tab-role">
      <form onsubmit={handleRoleSubmit}>
        <div class="space-y-12 rounded-lg bg-white px-4 py-4 sm:px-6 lg:px-8 dark:bg-black">
          <div>
            <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">Role</h2>
            <p class="mt-1 text-sm/6 text-gray-600 dark:text-gray-300">
              Role kamu: {myRoles.length ? myRoles.join(', ') : '—'}
            </p>

            <div class="mt-10 sm:col-span-2">
              <label
                for="userId"
                class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                >Pilih User</label
              >
              <div class="mt-2">
                <select
                  id="userId"
                  bind:value={selectedUserId}
                  onchange={handleUserChange}
                  disabled={pageLoading || !users.length}
                  class="block w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:ring-2 focus:ring-indigo-600 focus:outline-none dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
                >
                  {#if !users.length}
                    <option value="">Tidak ada user lain</option>
                  {:else}
                    {#each users as u (u.id)}
                      <option value={String(u.id)}>{u.name} ({u.email})</option>
                    {/each}
                  {/if}
                </select>
              </div>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
              <fieldset class="sm:col-span-3">
                <legend class="text-sm/6 font-semibold text-gray-900 dark:text-gray-100"
                  >Pilih Role</legend
                >
                <div class="mt-6 space-y-3">
                  {#each roleList as r (r.key)}
                    <div class="flex items-center gap-x-3">
                      <input
                        id="push-{r.key}"
                        type="radio"
                        bind:group={role.selectedRole}
                        value={r.key}
                        disabled={(currentIsOnlyAdmin && r.key === 'super_admin') ||
                          (currentIsOnlyAdmin && selectedUserIsSuperAdmin)}
                        class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white checked:border-indigo-600 checked:bg-indigo-600 focus:outline-indigo-600 dark:border-gray-600 dark:bg-neutral-900"
                      />
                      <label
                        for="push-{r.key}"
                        class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                      >
                        {r.label}
                      </label>
                    </div>
                  {/each}
                </div>
              </fieldset>

              <fieldset class="sm:col-span-3">
                <legend class="text-sm/6 font-semibold text-gray-900 dark:text-gray-100"
                  >Pilih Permission per Modul</legend
                >
                <div class="mt-4 space-y-3">
                  {#each moduleList as mod (mod.key)}
                    <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                      <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                          <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                              <input
                                type="checkbox"
                                class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-indigo-600 checked:bg-indigo-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:border-gray-600 dark:bg-neutral-900"
                                use:syncChecked={moduleAllChecked(mod.key)}
                                use:indeterminate={moduleSomeChecked(mod.key)}
                                onchange={(event) => handleModuleChange(event, mod.key)}
                              />
                              <svg
                                viewBox="0 0 14 14"
                                fill="none"
                                class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white"
                                ><path
                                  d="M3 8L6 11L11 3.5"
                                  stroke-width="2"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  class="opacity-0 group-has-checked:opacity-100"
                                /><path
                                  d="M3 7H11"
                                  stroke-width="2"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  class="opacity-0 group-has-indeterminate:opacity-100"
                                /></svg
                              >
                            </div>
                          </div>
                          <button
                            type="button"
                            class="flex items-center gap-1 text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                            onclick={() => (expandedModules[mod.key] = !expandedModules[mod.key])}
                          >
                            {mod.label}
                            <svg
                              class="size-3.5 transition-transform"
                              viewBox="0 0 20 20"
                              fill="none"
                              stroke="currentColor"
                              stroke-width="1.8"
                              class:rotate-90={expandedModules[mod.key]}
                              ><path
                                d="M7 5l6 5-6 5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                              /></svg
                            >
                          </button>
                        </div>
                      </div>

                      {#if expandedModules[mod.key]}
                        <div class="mt-3 space-y-2 pl-6">
                          {#each mod.actions ?? actionList as act (act.key)}
                            <div class="flex items-center gap-3">
                              <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 dark:border-gray-600"
                                checked={role.modules[mod.key]
                                  ? role.modules[mod.key][act.key]
                                  : false}
                                onchange={(event) => handleActionChange(event, mod.key, act.key)}
                              />
                              <span class="text-xs font-medium text-gray-800 dark:text-gray-200"
                                >{act.label}</span
                              >
                            </div>
                          {/each}
                        </div>
                      {/if}
                    </div>
                  {/each}
                </div>
              </fieldset>
            </div>

            <div class="mt-8 flex items-center justify-end gap-3">
              <button
                type="button"
                onclick={resetRoleToInitial}
                class="text-sm/6 font-semibold text-gray-900 dark:text-gray-200"
                disabled={!roleDirty || savingRole}>Reset</button
              >
              <button
                type="submit"
                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-60"
                disabled={!roleDirty ||
                  savingRole ||
                  pageLoading ||
                  !role.userId ||
                  (currentIsOnlyAdmin && selectedUserIsSuperAdmin)}
              >
                {savingRole ? 'Saving…' : 'Save'}
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  {/if}
</div>
