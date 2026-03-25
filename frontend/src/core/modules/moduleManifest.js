import AppLayout from '@/layout/AppLayout.vue';

function buildChildRoutes(moduleManifest) {
    return (moduleManifest.routes ?? []).map((route) => ({
        path: route.path,
        name: route.name,
        meta: {
            moduleKey: moduleManifest.key,
            ...(route.meta ?? {})
        },
        component: route.component
    }));
}

export function buildModuleRoutes(moduleManifests) {
    return moduleManifests
        .filter((moduleManifest) => (moduleManifest.routes ?? []).length > 0)
        .map((moduleManifest) => ({
            path: '/',
            meta: {
                requiresAuth: true
            },
            component: AppLayout,
            children: buildChildRoutes(moduleManifest)
        }));
}

export function buildModuleMenu(moduleManifests) {
    return moduleManifests
        .map((moduleManifest) => {
            const items = (moduleManifest.routes ?? [])
                .filter((route) => route.menu)
                .map((route) => ({
                    label: route.menu.label,
                    icon: route.menu.icon,
                    to: route.path,
                    moduleKey: moduleManifest.key,
                    permissionKey: route.meta?.permissionKey
                }));

            if (items.length === 0) {
                return null;
            }

            return {
                label: moduleManifest.navigation?.label ?? moduleManifest.name,
                moduleKey: moduleManifest.key,
                items
            };
        })
        .filter(Boolean);
}
