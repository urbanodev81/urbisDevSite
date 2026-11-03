import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import AcessibilidadeWidget from './Components/AcessibilidadeWidget.vue';

// O painel do site (03/11/2026, pendência 246). Só ele é Inertia.
const appName = document.querySelector('meta[name="application-name"]')?.content || 'Painel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({
            render: () => [h(App, props), h(AcessibilidadeWidget)],
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: 'rgb(var(--accent))',
    },
});
