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
        /*
        | CORS: Vite 6 solo acepta peticiones del mismo origen, y laravel-vite-plugin
        | reduce la lista permitida a `server.origin` cuando este se define. Sin esto,
        | la app (http://localhost:8000) no puede cargar los assets del dev server
        | (http://localhost:5173) y la página se queda EN BLANCO.
        | Permitimos cualquier puerto de localhost/127.0.0.1, igual que el defecto de Vite.
        */
        cors: {
            origin: [
                /^https?:\/\/localhost(:\d+)?$/,
                /^https?:\/\/127\.0\.0\.1(:\d+)?$/,
                /^https?:\/\/\[::1\](:\d+)?$/,
            ],
        },
    },
});
