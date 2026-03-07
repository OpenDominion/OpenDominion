import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'app/resources/sass/app.scss',
                'app/resources/js/app.js',
            ],
            publicDirectory: 'public',
            buildDirectory: 'assets/app',
            refresh: ['app/resources/views/**'],
        }),
    ],
    build: {
        rollupOptions: {
            external: ['jquery'],
            output: {
                globals: { jquery: 'jQuery' },
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler',
                // Silence deprecations in upstream packages (Bootstrap, AdminLTE)
                // that we cannot fix without modifying node_modules.
                silenceDeprecations: ['import', 'global-builtin', 'if-function', 'color-functions'],
            },
        },
    },
});
