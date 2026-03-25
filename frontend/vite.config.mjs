import { fileURLToPath, URL } from 'node:url';

import { PrimeVueResolver } from '@primevue/auto-import-resolver';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite';
import { defineConfig } from 'vite';

// https://vitejs.dev/config/
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('node_modules/primevue/')) {
                        const segment = id.split('node_modules/primevue/')[1]?.split('/')[0];
                        if (segment === 'config') {
                            return;
                        }

                        return segment ? `primevue-${segment}` : 'primevue-core';
                    }

                    if (id.includes('node_modules/@primeuix/')) {
                        const segment = id.split('node_modules/@primeuix/')[1]?.split('/')[0];
                        return segment ? `primeuix-${segment}` : 'primeuix-core';
                    }

                    if (id.includes('node_modules/primeicons/')) {
                        return 'primeicons';
                    }

                    if (id.includes('chart.js')) {
                        return 'charts';
                    }

                    if (id.includes('vue') || id.includes('vue-router') || id.includes('pinia')) {
                        return 'vue';
                    }

                    return 'vendor';
                }
            }
        }
    },
    optimizeDeps: {
        noDiscovery: true
    },
    plugins: [
        vue(),
        tailwindcss(),
        Components({
            resolvers: [PrimeVueResolver()]
        })
    ],
    server: {
        host: true,
        port: 5173,
        watch: {
            usePolling: true // Crucial para que Docker detecte cambios en Windows
        }
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url))
        }
    },
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler'
            }
        }
    }
});
