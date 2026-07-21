import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// IBM Plex — self-hosted через @fontsource (у app.css), без CDN (Принцип III).
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
