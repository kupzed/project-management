<script lang="ts">
  import type {
    ActionConfig,
    ModuleConfig,
    RoleForm,
    RoleUser
  } from '$lib/services/settingsService';

  /** Role and module permission editor. */
  let {
    myRoles,
    users,
    selectedUserId = $bindable(),
    role = $bindable(),
    roleList,
    moduleList,
    actionList,
    expandedModules = $bindable(),
    pageLoading,
    saving,
    dirty,
    currentIsOnlyAdmin,
    selectedUserIsSuperAdmin,
    onSelectUser,
    onToggleModule,
    onToggleAction,
    onReset,
    onSubmit
  }: {
    myRoles: string[];
    users: RoleUser[];
    selectedUserId: string;
    role: RoleForm;
    roleList: { key: string; label: string }[];
    moduleList: ModuleConfig[];
    actionList: ActionConfig[];
    expandedModules: Record<string, boolean>;
    pageLoading: boolean;
    saving: boolean;
    dirty: boolean;
    currentIsOnlyAdmin: boolean;
    selectedUserIsSuperAdmin: boolean;
    onSelectUser: (user: RoleUser | null) => void;
    onToggleModule: (moduleKey: string, checked: boolean) => void;
    onToggleAction: (moduleKey: string, actionKey: string, checked: boolean) => void;
    onReset: () => void;
    onSubmit: (event: SubmitEvent) => void;
  } = $props();

  function actionsFor(module: ModuleConfig): ActionConfig[] {
    return module.actions ?? actionList;
  }

  function moduleAllChecked(module: ModuleConfig): boolean {
    const state = role.modules[module.key];
    return Boolean(state && actionsFor(module).every((action) => state[action.key]));
  }

  function moduleSomeChecked(module: ModuleConfig): boolean {
    const state = role.modules[module.key];
    if (!state) return false;
    const actions = actionsFor(module);
    return (
      actions.some((action) => state[action.key]) && !actions.every((action) => state[action.key])
    );
  }

  function indeterminate(node: HTMLInputElement, value: boolean) {
    node.indeterminate = value;
    return { update: (nextValue: boolean) => (node.indeterminate = nextValue) };
  }

  function syncChecked(node: HTMLInputElement, value: boolean) {
    node.checked = value;
    return { update: (nextValue: boolean) => (node.checked = nextValue) };
  }

  function handleUserChange(event: Event): void {
    const id = Number((event.currentTarget as HTMLSelectElement).value);
    onSelectUser(users.find((user) => user.id === id) ?? null);
  }
</script>

<div id="panel-role" role="tabpanel" aria-labelledby="tab-role">
  <form onsubmit={onSubmit}>
    <div class="space-y-12 rounded-lg bg-white px-4 py-4 sm:px-6 lg:px-8 dark:bg-black">
      <div>
        <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">Role</h2>
        <p class="mt-1 text-sm/6 text-gray-600 dark:text-gray-300">
          Role kamu: {myRoles.length ? myRoles.join(', ') : '-'}
        </p>
        <div class="mt-10 sm:col-span-2">
          <label for="userId" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100"
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
                {#each users as user (user.id)}
                  <option value={String(user.id)}>{user.name} ({user.email})</option>
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
              {#each roleList as roleOption (roleOption.key)}
                <div class="flex items-center gap-x-3">
                  <input
                    id="push-{roleOption.key}"
                    type="radio"
                    bind:group={role.selectedRole}
                    value={roleOption.key}
                    disabled={(currentIsOnlyAdmin && roleOption.key === 'super_admin') ||
                      (currentIsOnlyAdmin && selectedUserIsSuperAdmin)}
                    class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white checked:border-indigo-600 checked:bg-indigo-600 focus:outline-indigo-600 dark:border-gray-600 dark:bg-neutral-900"
                  />
                  <label
                    for="push-{roleOption.key}"
                    class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                  >
                    {roleOption.label}
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
              {#each moduleList as module (module.key)}
                <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                  <div class="flex items-center gap-3">
                    <input
                      type="checkbox"
                      class="size-4 rounded-sm border border-gray-300"
                      use:syncChecked={moduleAllChecked(module)}
                      use:indeterminate={moduleSomeChecked(module)}
                      onchange={(event) => onToggleModule(module.key, event.currentTarget.checked)}
                    />
                    <button
                      type="button"
                      class="flex items-center gap-1 text-sm/6 font-medium text-gray-900 dark:text-gray-100"
                      onclick={() => (expandedModules[module.key] = !expandedModules[module.key])}
                    >
                      {module.label}
                    </button>
                  </div>
                  {#if expandedModules[module.key]}
                    <div class="mt-3 space-y-2 pl-6">
                      {#each actionsFor(module) as action (action.key)}
                        <label class="flex items-center gap-3">
                          <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 dark:border-gray-600"
                            checked={role.modules[module.key]?.[action.key] ?? false}
                            onchange={(event) =>
                              onToggleAction(module.key, action.key, event.currentTarget.checked)}
                          />
                          <span class="text-xs font-medium text-gray-800 dark:text-gray-200"
                            >{action.label}</span
                          >
                        </label>
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
            onclick={onReset}
            class="text-sm/6 font-semibold text-gray-900 dark:text-gray-200"
            disabled={!dirty || saving}>Reset</button
          >
          <button
            type="submit"
            class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-60"
            disabled={!dirty ||
              saving ||
              pageLoading ||
              !role.userId ||
              (currentIsOnlyAdmin && selectedUserIsSuperAdmin)}
          >
            {saving ? 'Saving...' : 'Save'}
          </button>
        </div>
      </div>
    </div>
  </form>
</div>
