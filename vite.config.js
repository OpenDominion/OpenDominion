import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath } from 'url';

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
    resolve: {
        alias: {
            // Satisfy `import $ from 'jquery'` in the bundle by returning the
            // jQuery instance already loaded via the synchronous classic <script>.
            jquery: fileURLToPath(new URL('./app/resources/js/jquery-global.js', import.meta.url)),
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
