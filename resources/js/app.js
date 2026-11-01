import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import AppLayout from './layouts/AppLayout.vue';

const pages = import.meta.glob('./pages/**/*.vue', { eager: true });

createInertiaApp({
    title: (title) => (title ? `${title} · Учёт времени` : 'Учёт времени'),
    resolve: (name) => pages[`./pages/${name}.vue`],
    layout: () => AppLayout,
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: '#a1a1aa', showSpinner: false },
});
