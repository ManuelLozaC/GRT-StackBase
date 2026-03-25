import { buildModuleMenu, buildModuleRoutes } from '@/core/modules/moduleManifest';
import { demoPlatformManifest } from '@/modules/demo-platform/manifest';

export const installedModules = [demoPlatformManifest];

export const moduleRoutes = buildModuleRoutes(installedModules);

export const moduleMenu = buildModuleMenu(installedModules);
