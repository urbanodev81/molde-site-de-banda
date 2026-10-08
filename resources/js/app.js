import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import AcessibilidadeWidget from './Components/AcessibilidadeWidget.vue';
import ReportarErro from './Components/ReportarErro.vue';
import VLibrasWidget from './Components/VLibrasWidget.vue';
import InstalarApp from './Components/InstalarApp.vue';
import FeedbackDeTela from './Components/FeedbackDeTela.vue';
import { registrarPwa } from './pwa';

const appName = import.meta.env.VITE_APP_NAME || 'A melhor banda';

createInertiaApp({

    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {

        return createApp({
            render: () => [
                h(App, props),
                h(AcessibilidadeWidget),
                h(ReportarErro),
                h(VLibrasWidget),
                h(InstalarApp),
                h(FeedbackDeTela),
            ],
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

registrarPwa();
