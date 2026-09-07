import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/game-overlay.css',
                'resources/js/app.js',
                'resources/js/game-live.js',
                'resources/js/game-overlay.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
