import { demoPlatformRegistry } from '@/modules/demo-platform/registry';

const registries = {
    'demo-platform': demoPlatformRegistry
};

export function resolveModuleView(moduleKey, viewKey) {
    return registries[moduleKey]?.views?.[viewKey] ?? null;
}
