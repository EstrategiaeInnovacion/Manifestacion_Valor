import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/welcome.css',
                'resources/css/dashboard.css',
                'resources/css/login.css',
                'resources/css/profile.css',
                'resources/css/users-list.css',
                'resources/css/add-user.css',
                'resources/css/applicants-list.css',
                'resources/css/applicant-form.css',
                'resources/css/mve-create.css',
                'resources/css/mve-manual.css',
                'resources/css/mve-select.css',
                'resources/css/mve-upload.css',
                'resources/js/app.js',
                'resources/js/welcome.js',
                'resources/js/dashboard.js',
                'resources/js/login.js',
                'resources/js/profile.js',
                'resources/js/users-list.js',
                'resources/js/add-user.js',
                'resources/js/applicants-list.js',
                'resources/js/applicant-form.js',
                'resources/js/mve-manual.js',
                'resources/js/mve-select.js',
                'resources/js/mve-upload.js',
                'resources/js/mve-pendientes.js',
                'resources/js/edocument-consulta.js',
            ],
            refresh: true,
        }),
    ],
});
