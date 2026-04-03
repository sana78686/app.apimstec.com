import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Keep Ziggy in sync with Inertia (defaults must include cms_locale for /{locale}/… routes).
 * Blade @routes can lack defaults; props.ziggy should not, but we merge cmsLocale as a safeguard.
 */
function applyZiggyFromPage(page) {
    const z = page?.props?.ziggy;
    if (!z || typeof globalThis === 'undefined') {
        return;
    }
    const loc = page?.props?.cmsLocale ?? z.defaults?.cms_locale ?? 'id';
    z.defaults = { ...(z.defaults && typeof z.defaults === 'object' ? z.defaults : {}), cms_locale: loc };
    globalThis.Ziggy = z;
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

        router.on('navigate', (event) => {
            applyZiggyFromPage(event.detail.page);
        });

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
