import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/** Sync Ziggy from Inertia shared props after navigation (e.g. login → dashboard). */
function applyZiggyFromPage(page) {
    if (typeof globalThis === 'undefined') {
        return;
    }

    const z = page?.props?.ziggy;
    if (z && typeof z === 'object') {
        globalThis.Ziggy = z;
    }
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        applyZiggyFromPage(props.initialPage);

        function syncZiggyFromRouterEvent(event) {
            applyZiggyFromPage(event.detail?.page);
        }

        router.on('navigate', syncZiggyFromRouterEvent);
        router.on('success', syncZiggyFromRouterEvent);

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
