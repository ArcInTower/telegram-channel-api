import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/js/highlight.js',
                'resources/js/statistics.js',
                'resources/js/compare.js'
            ],
            refresh: true,
        }),
    ],
});
