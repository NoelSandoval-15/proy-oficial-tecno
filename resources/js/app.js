import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/*
|--------------------------------------------------------------------------
| Ziggy en localhost y servidor Tecnoweb
|--------------------------------------------------------------------------
|
| Localhost:
| http://127.0.0.1:8000
| http://localhost:8000
|
| Servidor:
| https://www.tecnoweb.org.bo/inf513/grupo17sc/proyecto2
|
| Importante:
| Para evitar duplicación de rutas, dejamos `url` solo con el dominio
| y agregamos la subcarpeta al `uri` de cada ruta.
|
*/

const productionBasePath = '/inf513/grupo17sc/proyecto2';

const isLocalhost = [
    'localhost',
    '127.0.0.1',
].includes(window.location.hostname);

const basePath = isLocalhost ? '' : productionBasePath;
const cleanBasePath = basePath.replace(/^\/|\/$/g, '');

const globalZiggy = window.Ziggy || {};
const originalRoutes = globalZiggy.routes || {};

const prefixedRoutes = Object.entries(originalRoutes).reduce((routes, [name, route]) => {
    let uri = String(route.uri || '').replace(/^\/+/, '');

    if (!isLocalhost && cleanBasePath) {
        const alreadyPrefixed =
            uri === cleanBasePath ||
            uri.startsWith(`${cleanBasePath}/`);

        if (!alreadyPrefixed) {
            uri = `${cleanBasePath}/${uri}`;
        }
    }

    routes[name] = {
        ...route,
        uri,
    };

    return routes;
}, {});

const ziggyConfig = {
    ...globalZiggy,
    url: window.location.origin,
    routes: prefixedRoutes,
    location: new URL(window.location.href),
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')
    ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, ziggyConfig)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
