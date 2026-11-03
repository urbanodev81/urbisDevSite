import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

/*
 * Build só do PAINEL (03/11/2026, pendência 246). O site público não passa
 * pelo Vite: segue Blade com CSS e JS próprios em `public/`. A saída vai para
 * `public/build`.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                compilerOptions: {
                    // O captcha do login é o web component do Altcha.
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
