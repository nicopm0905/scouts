import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    // El servidor de dev escucha en 0.0.0.0 (dentro de Docker) pero anuncia
    // localhost al navegador, para que los assets/HMR se carguen bien desde el host.
    server: {
        host: '0.0.0.0',
        port: 5173,
        origin: 'http://localhost:5173',
        hmr: {
            host: 'localhost',
        },
    },
});
