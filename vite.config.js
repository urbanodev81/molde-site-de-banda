import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({

            input: [
                'resources/js/app.js',
                'resources/css/site.css',
                'resources/css/a11y.css',
                'resources/js/site.js',

                'resources/css/previa-kit.css',
                'resources/css/previa-v3.css',
            ],
            refresh: true,
        }),
        vue({
            template: {
                compilerOptions: {

                    isCustomElement: (tag) => tag.startsWith('altcha-'),
                },
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
