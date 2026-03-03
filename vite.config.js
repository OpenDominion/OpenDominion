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
});
