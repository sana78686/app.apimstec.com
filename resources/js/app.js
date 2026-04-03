import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const CMS_LOCALE_PATH_RE = /^\/(id|en|ms|es|fr|ar|ru)(?=\/|$)/;

/** Infer /{cms_locale}/… from the browser URL when props are not ready yet (e.g. right after login). */
function inferCmsLocaleFromUrl() {
    if (typeof window === 'undefined') return null;
    const m = String(window.location.pathname || '').match(CMS_LOCALE_PATH_RE);
    return m ? m[1] : null;
}

function normalizeZiggyDefaults(defaults) {
    if (defaults && typeof defaults === 'object' && !Array.isArray(defaults)) {
        return { ...defaults };
    }
    return {};
}

/**
 * Keep Ziggy in sync with Inertia (defaults must include cms_locale for /{cms_locale}/… routes).
 * After login, the document may still use @routes from /login (no cms_locale in URL defaults).
 * If props.ziggy is missing, we still patch global Ziggy so route() never throws.
 */
function applyZiggyFromPage(page) {
    if (typeof globalThis === 'undefined') {
        return;
    }

    const loc =
        page?.props?.cmsLocale ??
        inferCmsLocaleFromUrl() ??
        page?.props?.ziggy?.defaults?.cms_locale ??
        globalThis.Ziggy?.defaults?.cms_locale ??
        'en';

    const z = page?.props?.ziggy;
    if (z && typeof z === 'object') {
        const next = { ...z, defaults: normalizeZiggyDefaults(z.defaults) };
        next.defaults.cms_locale = loc;
        globalThis.Ziggy = next;
        return;
    }

    if (globalThis.Ziggy && typeof globalThis.Ziggy === 'object') {
        const next = { ...globalThis.Ziggy, defaults: normalizeZiggyDefaults(globalThis.Ziggy.defaults) };
        next.defaults.cms_locale = loc;
        globalThis.Ziggy = next;
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
