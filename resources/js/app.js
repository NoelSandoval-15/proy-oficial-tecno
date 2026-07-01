import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/*
|--------------------------------------------------------------------------
| Configuración de rutas para localhost y Tecnoweb
|--------------------------------------------------------------------------
|
| Localhost:
| http://127.0.0.1:8000
| http://localhost:8000
|
| Servidor:
| https://www.tecnoweb.org.bo/inf513/grupo17sc/proyecto2
|
*/

const productionBasePath = '/inf513/grupo17sc/proyecto2';

const isLocalhost = [
    'localhost',
    '127.0.0.1',
].includes(window.location.hostname);

/*
|--------------------------------------------------------------------------
| Corrección automática de URL duplicada
|--------------------------------------------------------------------------
|
| Si por caché, historial o navegación anterior entra a:
| /inf513/grupo17sc/proyecto2/inf513/grupo17sc/proyecto2/
|
| lo mandamos automáticamente a:
| /inf513/grupo17sc/proyecto2/
|
*/

if (!isLocalhost) {
    const duplicatedBasePath = `${productionBasePath}${productionBasePath}`;

    if (window.location.pathname.startsWith(duplicatedBasePath)) {
        const fixedPath = window.location.pathname.replace(
            duplicatedBasePath,
            productionBasePath
        );

        window.location.replace(
            `${window.location.origin}${fixedPath}${window.location.search}${window.location.hash}`
        );
    }
}

const globalZiggy = window.Ziggy || {};

const ziggyConfig = {
    ...globalZiggy,
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
