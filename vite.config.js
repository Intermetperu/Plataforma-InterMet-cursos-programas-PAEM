import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// La plantilla (Bootstrap 5, Font Awesome, jQuery, etc.) está en public/assets.
// Vite solo compila los estilos y scripts propios del sistema.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
