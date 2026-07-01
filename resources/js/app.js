import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/*
|--------------------------------------------------------------------------
| Configuración de rutas para Ziggy
|--------------------------------------------------------------------------
|
| Localhost:
| En local normalmente Laravel corre en:
| http://127.0.0.1:8000
| http://localhost:8000
|
| En local NO se necesita subcarpeta.
|
| Ejemplo local comentado:
| const basePath = '';
|
| Servidor Tecnoweb:
| La app vive dentro de:
| https://www.tecnoweb.org.bo/inf513/grupo17sc/proyecto2
|
*/

const productionBasePath = '/inf513/grupo17sc/proyecto2';

const isLocalhost = [
    'localhost',
    '127.0.0.1',
].includes(window.location.hostname);

const basePath = isLocalhost ? '' : productionBasePath;

const ziggyConfig = {
    ...(window.Ziggy || {}),
    url: `${window.location.origin}${basePath}`,
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
