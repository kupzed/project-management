import type {
  ActionConfig,
  ModuleConfig,
  ModulesState,
  RoleForm
} from '$lib/services/settingsService';

export function createEmptyModules(
  moduleList: ModuleConfig[],
  actionList: ActionConfig[]
): ModulesState {
  const modules: ModulesState = {};
  for (const module of moduleList) {
    modules[module.key] = {};
    for (const action of module.actions ?? actionList) {
      modules[module.key][action.key] = false;
    }
  }
  return modules;
}

export function cloneModules(modules: ModulesState): ModulesState {
  // Avoid structuredClone() — Svelte 5 $state proxies are not cloneable by it.
  // ModulesState only contains nested boolean primitives, so a manual deep
  // copy via Object.entries + spread is sufficient and proxy-safe.
  const clone: ModulesState = {};
  for (const [moduleKey, actions] of Object.entries(modules)) {
    clone[moduleKey] = { ...actions };
  }
  return clone;
}

export function buildPermissionsPayload(
  role: RoleForm,
  moduleList: ModuleConfig[],
  actionList: ActionConfig[]
): Record<string, boolean> {
  const permissions: Record<string, boolean> = {};
  for (const module of moduleList) {
    const moduleState = role.modules[module.key];
    if (!moduleState) continue;
    for (const action of module.actions ?? actionList) {
      permissions[`${module.key}-${action.key}`] = moduleState[action.key];
    }
  }
  return permissions;
}
