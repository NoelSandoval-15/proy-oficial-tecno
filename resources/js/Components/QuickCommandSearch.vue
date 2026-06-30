<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const root = ref(null);
const open = ref(false);
const search = ref('');

const currentRoles = computed(() => {
    const user = page.props.auth?.user ?? null;

    const roles =
        user?.roles ??
        user?.role_names ??
        user?.roleNames ??
        null;

    if (Array.isArray(roles)) {
        return roles
            .map((role) => {
                if (typeof role === 'string') {
                    return role;
                }

                return role?.name;
            })
            .filter(Boolean);
    }

    if (roles && typeof roles === 'object') {
        return Object.values(roles)
            .map((role) => {
                if (typeof role === 'string') {
                    return role;
                }

                return role?.name;
            })
            .filter(Boolean);
    }

    const singleRole =
        user?.role ??
        user?.user_role ??
        user?.role_name ??
        null;

    return singleRole ? [singleRole] : [];
});

const can = (roles) => {
    return roles.some((role) => currentRoles.value.includes(role));
};

const rolesText = computed(() => {
    return currentRoles.value.length ? currentRoles.value.join(', ') : 'Sin rol';
});

const today = computed(() => {
    const currentDate = new Date();
    const year = currentDate.getFullYear();
    const month = String(currentDate.getMonth() + 1).padStart(2, '0');
    const day = String(currentDate.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
});

const routeOrNull = (routeName) => {
    try {
        if (typeof route !== 'function') {
            return null;
        }

        try {
            const routerInstance = route();

            if (routerInstance?.has && !routerInstance.has(routeName)) {
                return null;
            }
        } catch {
            // Si Ziggy no permite route().has, intentamos generar la ruta directo.
        }

        return route(routeName);
    } catch {
        return null;
    }
};

const routeWithQuery = (routeName, query = {}) => {
    const baseUrl = routeOrNull(routeName);

    if (!baseUrl) {
        return null;
    }

    const params = new URLSearchParams();

    Object.entries(query).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            params.append(key, value);
        }
    });

    const queryString = params.toString();

    return queryString ? `${baseUrl}?${queryString}` : baseUrl;
};

const makeAction = ({
    key,
    title,
    description,
    category,
    roles,
    routeName,
    query = {},
    icon,
    keywords = [],
}) => {
    const href = routeWithQuery(routeName, query);

    if (!href) {
        return null;
    }

    return {
        key,
        title,
        description,
        category,
        roles,
        href,
        icon,
        keywords,
    };
};

const baseActions = computed(() => {
    return [
        makeAction({
            key: 'dashboard',
            title: 'Ir al dashboard',
            description: 'Volver al panel principal del sistema.',
            category: 'General',
            roles: ['Master', 'Administrador', 'Mesero', 'Cliente'],
            routeName: 'dashboard',
            icon: 'dashboard',
            keywords: ['inicio', 'panel principal', 'home'],
        }),

        makeAction({
            key: 'profile',
            title: 'Mi perfil',
            description: 'Ver y editar mis datos de cuenta.',
            category: 'Cuenta',
            roles: ['Master', 'Administrador', 'Mesero', 'Cliente'],
            routeName: 'profile.edit',
            icon: 'user',
            keywords: ['cuenta', 'usuario', 'perfil'],
        }),

        makeAction({
            key: 'products-index',
            title: 'Visualizar productos',
            description: 'Listar, buscar, filtrar, editar y exportar productos.',
            category: 'Productos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.index',
            icon: 'products',
            keywords: ['producto', 'productos', 'platos', 'bebidas', 'stock', 'inventario'],
        }),

        makeAction({
            key: 'products-create',
            title: 'Añadir producto',
            description: 'Registrar un nuevo producto con precio, stock, subcategoría e imagen.',
            category: 'Productos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.index',
            query: {
                action: 'create',
            },
            icon: 'plus',
            keywords: ['crear producto', 'nuevo producto', 'agregar producto', 'registrar producto'],
        }),

        makeAction({
            key: 'products-low-stock',
            title: 'Productos con stock bajo',
            description: 'Ver productos con cantidad baja para reponer inventario.',
            category: 'Productos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.index',
            query: {
                stock_status: 'low',
            },
            icon: 'warning',
            keywords: ['stock bajo', 'cantidad baja', 'reponer', 'alerta'],
        }),

        makeAction({
            key: 'products-out-stock',
            title: 'Productos sin stock',
            description: 'Ver productos agotados o con cantidad cero.',
            category: 'Productos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.index',
            query: {
                stock_status: 'out',
            },
            icon: 'warning',
            keywords: ['sin stock', 'agotado', 'agotados', 'cantidad cero'],
        }),

        makeAction({
            key: 'products-export-excel',
            title: 'Exportar productos Excel',
            description: 'Descargar productos en formato Excel.',
            category: 'Exportaciones productos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.export.excel',
            icon: 'excel',
            keywords: ['excel productos', 'exportar productos', 'xlsx'],
        }),

        makeAction({
            key: 'products-export-pdf',
            title: 'Exportar productos PDF',
            description: 'Generar reporte PDF de productos.',
            category: 'Exportaciones productos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.export.pdf',
            icon: 'pdf',
            keywords: ['pdf productos', 'reporte productos'],
        }),

        makeAction({
            key: 'products-export-txt',
            title: 'Exportar productos TXT',
            description: 'Descargar productos en formato de texto.',
            category: 'Exportaciones productos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.export.txt',
            icon: 'txt',
            keywords: ['txt productos', 'texto productos'],
        }),

        makeAction({
            key: 'categories-index',
            title: 'Visualizar categorías',
            description: 'Gestionar categorías y subcategorías de productos.',
            category: 'Categorías',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.categories.index',
            icon: 'categories',
            keywords: ['categoria', 'categorias', 'subcategoria', 'subcategorias'],
        }),

        makeAction({
            key: 'categories-create',
            title: 'Añadir categoría',
            description: 'Crear una nueva categoría de productos.',
            category: 'Categorías',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.categories.index',
            query: {
                action: 'create-category',
            },
            icon: 'plus',
            keywords: ['crear categoria', 'nueva categoria', 'agregar categoria'],
        }),

        makeAction({
            key: 'subcategories-create',
            title: 'Añadir subcategoría',
            description: 'Crear una subcategoría dentro de una categoría.',
            category: 'Categorías',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.categories.index',
            query: {
                action: 'create-subcategory',
            },
            icon: 'plus',
            keywords: ['crear subcategoria', 'nueva subcategoria', 'agregar subcategoria'],
        }),

        makeAction({
            key: 'categories-export-excel',
            title: 'Exportar categorías Excel',
            description: 'Descargar categorías y subcategorías en formato Excel.',
            category: 'Exportaciones categorías',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.categories.export.excel',
            icon: 'excel',
            keywords: ['excel categorias', 'exportar categorias', 'xlsx categorias'],
        }),

        makeAction({
            key: 'categories-export-pdf',
            title: 'Exportar categorías PDF',
            description: 'Generar reporte PDF de categorías y subcategorías.',
            category: 'Exportaciones categorías',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.categories.export.pdf',
            icon: 'pdf',
            keywords: ['pdf categorias', 'reporte categorias'],
        }),

        makeAction({
            key: 'categories-export-txt',
            title: 'Exportar categorías TXT',
            description: 'Descargar categorías y subcategorías en formato de texto.',
            category: 'Exportaciones categorías',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'products.categories.export.txt',
            icon: 'txt',
            keywords: ['txt categorias', 'texto categorias'],
        }),

        makeAction({
            key: 'employees-index',
            title: 'Visualizar empleados',
            description: 'Listar, buscar, editar y exportar empleados.',
            category: 'Administración',
            roles: ['Master', 'Administrador'],
            routeName: 'administracion.empleados.index',
            icon: 'users',
            keywords: ['empleados', 'meseros', 'personal'],
        }),

        makeAction({
            key: 'employees-create',
            title: 'Añadir empleado',
            description: 'Registrar un nuevo empleado con rol Mesero.',
            category: 'Administración',
            roles: ['Master', 'Administrador'],
            routeName: 'administracion.empleados.index',
            query: {
                action: 'create',
            },
            icon: 'plus',
            keywords: ['crear empleado', 'nuevo empleado', 'agregar empleado'],
        }),

        makeAction({
            key: 'clients-index',
            title: 'Visualizar clientes',
            description: 'Listar clientes, reservas y compras registradas.',
            category: 'Administración',
            roles: ['Master', 'Administrador'],
            routeName: 'administracion.clientes.index',
            icon: 'clients',
            keywords: ['clientes', 'cliente'],
        }),

        makeAction({
            key: 'clients-create',
            title: 'Añadir cliente',
            description: 'Registrar un nuevo cliente en el sistema.',
            category: 'Administración',
            roles: ['Master', 'Administrador'],
            routeName: 'administracion.clientes.index',
            query: {
                action: 'create',
            },
            icon: 'plus',
            keywords: ['crear cliente', 'nuevo cliente', 'agregar cliente'],
        }),

        makeAction({
            key: 'admins-index',
            title: 'Visualizar administradores',
            description: 'Revisar usuarios con rol Administrador.',
            category: 'Seguridad',
            roles: ['Master', 'Administrador'],
            routeName: 'administracion.administradores.index',
            icon: 'shield',
            keywords: ['administradores', 'seguridad'],
        }),

        makeAction({
            key: 'admins-create',
            title: 'Añadir administrador',
            description: 'Registrar un usuario administrador.',
            category: 'Seguridad',
            roles: ['Master'],
            routeName: 'administracion.administradores.index',
            query: {
                action: 'create',
            },
            icon: 'shield',
            keywords: ['crear administrador', 'nuevo administrador'],
        }),

        makeAction({
            key: 'search-users',
            title: 'Buscar usuario',
            description: 'Consultar datos y estadísticas por usuario.',
            category: 'Consultas',
            roles: ['Master', 'Administrador'],
            routeName: 'administracion.usuarios.buscar',
            icon: 'search',
            keywords: ['buscar usuario', 'consultar usuario'],
        }),

        makeAction({
            key: 'audit-log',
            title: 'Ver bitácora',
            description: 'Revisar acciones, accesos y movimientos del sistema.',
            category: 'Seguridad',
            roles: ['Master', 'Administrador'],
            routeName: 'administracion.bitacora.index',
            icon: 'clock',
            keywords: ['bitacora', 'auditoria', 'logs'],
        }),

        makeAction({
            key: 'reservation-tables-index',
            title: 'Visualizar mesas',
            description: 'Gestionar mesas, capacidad y estado físico.',
            category: 'Reservas',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'reservation.tables.index',
            icon: 'tables',
            keywords: ['mesas', 'mesa', 'capacidad', 'salón', 'disponible', 'inactiva', 'mantenimiento'],
        }),

        makeAction({
            key: 'reservation-tables-create',
            title: 'Añadir mesa',
            description: 'Registrar una nueva mesa para reservas y ventas.',
            category: 'Reservas',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'reservation.tables.index',
            query: {
                action: 'create',
            },
            icon: 'plus',
            keywords: ['crear mesa', 'nueva mesa', 'agregar mesa', 'registrar mesa'],
        }),

        makeAction({
            key: 'admin-reservations-index',
            title: 'Visualizar reservas',
            description: 'Gestionar reservas internas, clientes, mesas y estados.',
            category: 'Reservas',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.reservations.index',
            icon: 'calendar',
            keywords: ['reservas', 'reserva', 'reservaciones', 'mesa reservada', 'cliente reserva'],
        }),

        makeAction({
            key: 'admin-reservations-create',
            title: 'Añadir reserva interna',
            description: 'Registrar una reserva seleccionando cliente, fecha, hora y mesas.',
            category: 'Reservas',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.reservations.index',
            query: {
                action: 'create',
            },
            icon: 'reservation',
            keywords: ['crear reserva', 'nueva reserva', 'agregar reserva', 'registrar reserva', 'reservar mesa'],
        }),

        makeAction({
            key: 'admin-reservations-pending',
            title: 'Reservas pendientes',
            description: 'Ver reservas que todavía están pendientes de confirmación.',
            category: 'Reservas',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.reservations.index',
            query: {
                state: 'Pendiente',
            },
            icon: 'clock',
            keywords: ['reservas pendientes', 'pendiente', 'confirmar reserva'],
        }),

        makeAction({
            key: 'admin-reservations-confirmed',
            title: 'Reservas confirmadas',
            description: 'Ver reservas confirmadas para atención.',
            category: 'Reservas',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.reservations.index',
            query: {
                state: 'Confirmada',
            },
            icon: 'calendar',
            keywords: ['reservas confirmadas', 'confirmada', 'confirmadas'],
        }),

        makeAction({
            key: 'admin-reservations-in-process',
            title: 'Reservas en proceso',
            description: 'Ver reservas que están siendo atendidas.',
            category: 'Reservas',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.reservations.index',
            query: {
                state: 'En Proceso',
            },
            icon: 'reservation',
            keywords: ['reservas en proceso', 'en proceso', 'atendiendo reserva'],
        }),

        makeAction({
            key: 'client-reservations-index',
            title: 'Mis reservas',
            description: 'Ver historial y estado de mis reservas.',
            category: 'Reservas cliente',
            roles: ['Cliente'],
            routeName: 'client.reservations.index',
            icon: 'calendar',
            keywords: ['mis reservas', 'mi reserva', 'historial de reservas', 'estado reserva'],
        }),

        makeAction({
            key: 'client-reservations-create',
            title: 'Reservar mesa',
            description: 'Crear una nueva reserva seleccionando fecha, hora y mesas disponibles.',
            category: 'Reservas cliente',
            roles: ['Cliente'],
            routeName: 'client.reservations.index',
            query: {
                action: 'create',
            },
            icon: 'reservation',
            keywords: ['reservar', 'reservar mesa', 'nueva reserva', 'crear reserva', 'hacer reserva'],
        }),

        makeAction({
            key: 'admin-orders-index',
            title: 'Visualizar pedidos',
            description: 'Gestionar pedidos internos, ventas, mesas, mostrador y pedidos para llevar.',
            category: 'Pedidos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.orders.index',
            icon: 'orders',
            keywords: ['pedidos', 'pedido', 'ventas', 'venta', 'comanda', 'comandas', 'atención', 'mesero'],
        }),

        makeAction({
            key: 'admin-orders-create',
            title: 'Nuevo pedido interno',
            description: 'Registrar pedido de mesa, mostrador o para llevar con productos por categoría.',
            category: 'Pedidos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.orders.index',
            query: {
                action: 'create',
            },
            icon: 'cart',
            keywords: ['crear pedido', 'nuevo pedido', 'registrar pedido', 'hacer pedido', 'anotar pedido'],
        }),

        makeAction({
            key: 'admin-orders-pending',
            title: 'Pedidos pendientes',
            description: 'Ver pedidos que todavía esperan atención o confirmación.',
            category: 'Pedidos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.orders.index',
            query: {
                status: 'Pendiente',
            },
            icon: 'clock',
            keywords: ['pedidos pendientes', 'pendiente', 'sin atender', 'por atender'],
        }),

        makeAction({
            key: 'admin-orders-preparing',
            title: 'Pedidos en preparación',
            description: 'Ver pedidos que cocina o atención está preparando.',
            category: 'Pedidos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.orders.index',
            query: {
                status: 'En preparación',
            },
            icon: 'chef',
            keywords: ['preparacion', 'preparación', 'en preparación', 'cocina', 'cocinando'],
        }),

        makeAction({
            key: 'admin-orders-ready',
            title: 'Pedidos listos',
            description: 'Ver pedidos listos para entregar al cliente.',
            category: 'Pedidos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.orders.index',
            query: {
                status: 'Listo',
            },
            icon: 'ready',
            keywords: ['listo', 'listos', 'pedido listo', 'entregar', 'para entregar'],
        }),

        makeAction({
            key: 'admin-orders-delivered',
            title: 'Pedidos entregados',
            description: 'Ver pedidos que ya fueron entregados.',
            category: 'Pedidos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.orders.index',
            query: {
                status: 'Entregado',
            },
            icon: 'ready',
            keywords: ['entregado', 'entregados', 'servido', 'servidos'],
        }),

        makeAction({
            key: 'admin-orders-table',
            title: 'Pedidos en mesa',
            description: 'Filtrar pedidos realizados desde una mesa del restaurante.',
            category: 'Pedidos por tipo',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.orders.index',
            query: {
                order_type: 'Mesa',
            },
            icon: 'tables',
            keywords: ['pedido en mesa', 'mesa', 'mesas', 'atención mesa'],
        }),

        makeAction({
            key: 'admin-orders-takeaway',
            title: 'Pedidos para llevar',
            description: 'Filtrar pedidos solicitados para llevar.',
            category: 'Pedidos por tipo',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.orders.index',
            query: {
                order_type: 'Para llevar',
            },
            icon: 'takeaway',
            keywords: ['para llevar', 'llevar', 'takeaway', 'recoger', 'recogida'],
        }),

        makeAction({
            key: 'admin-orders-counter',
            title: 'Ventas de mostrador',
            description: 'Filtrar ventas o pedidos hechos sin mesa ni cliente registrado.',
            category: 'Pedidos por tipo',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.orders.index',
            query: {
                order_type: 'Mostrador',
            },
            icon: 'counter',
            keywords: ['mostrador', 'venta mostrador', 'sin mesa', 'sin cliente'],
        }),

        makeAction({
            key: 'admin-orders-today',
            title: 'Pedidos de hoy',
            description: 'Ver pedidos y ventas registrados en la fecha actual.',
            category: 'Pedidos',
            roles: ['Master', 'Administrador', 'Mesero'],
            routeName: 'admin.orders.index',
            query: {
                date: today.value,
            },
            icon: 'money',
            keywords: ['hoy', 'ventas hoy', 'pedidos hoy', 'recaudación', 'recaudacion del dia'],
        }),

        makeAction({
            key: 'client-orders-index',
            title: 'Mis pedidos',
            description: 'Ver historial, estado y detalle de mis pedidos realizados.',
            category: 'Pedidos cliente',
            roles: ['Cliente'],
            routeName: 'client.orders.index',
            icon: 'orders',
            keywords: ['mis pedidos', 'mi pedido', 'historial pedidos', 'compras', 'mis compras'],
        }),

        makeAction({
            key: 'client-orders-create',
            title: 'Realizar pedido',
            description: 'Seleccionar categoría, subcategoría, productos, mesa o para llevar.',
            category: 'Pedidos cliente',
            roles: ['Cliente'],
            routeName: 'client.orders.index',
            query: {
                action: 'create',
            },
            icon: 'cart',
            keywords: ['realizar pedido', 'hacer pedido', 'nuevo pedido', 'pedir comida', 'comprar', 'carrito'],
        }),
    ].filter(Boolean);
});

const visibleActions = computed(() => {
    const text = search.value.toLowerCase().trim();

    return baseActions.value
        .filter((action) => can(action.roles))
        .filter((action) => {
            if (!text) {
                return true;
            }

            return [
                action.title,
                action.description,
                action.category,
                ...(action.keywords ?? []),
            ]
                .join(' ')
                .toLowerCase()
                .includes(text);
        });
});

const groupedActions = computed(() => {
    return visibleActions.value.reduce((groups, action) => {
        if (!groups[action.category]) {
            groups[action.category] = [];
        }

        groups[action.category].push(action);

        return groups;
    }, {});
});

const iconPath = (icon) => {
    const icons = {
        dashboard: 'M4 5h7v7H4V5zm9 0h7v5h-7V5zM4 14h7v5H4v-5zm9-2h7v7h-7v-7z',
        user: 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0',
        users: 'M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m0-4a4 4 0 118 0 4 4 0 01-8 0z',
        clients: 'M16 11a4 4 0 100-8 4 4 0 000 8zM8 13a4 4 0 100-8 4 4 0 000 8zM2 21a6 6 0 0112 0M12 21a6 6 0 0110 0',
        shield: 'M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4z',
        plus: 'M12 5v14M5 12h14',
        search: 'M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z',
        products: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        categories: 'M4 6h16M4 12h16M4 18h10',
        warning: 'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
        excel: 'M4 4h10l6 6v10a2 2 0 01-2 2H4V4zm10 0v6h6M8 13l2 3m0 0l2-3m-2 3l-2 3m2-3l2 3',
        pdf: 'M7 3h7l5 5v13H7V3zm7 0v5h5M5 9H3v9h2m4-5h1.5a1.5 1.5 0 010 3H9v2m5-5v5m0-5h2a2 2 0 010 4h-2',
        txt: 'M6 3h8l4 4v14H6V3zm8 0v4h4M9 12h6M9 16h6M9 8h2',
        clock: 'M12 8v5l3 2m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        tables: 'M3 10h18M5 10l1.5 10M18.5 20L20 10M8 10V6a4 4 0 018 0v4M7 20h10',
        calendar: 'M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z',
        reservation: 'M9 12l2 2 4-4M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z',
        orders: 'M7 3h10a2 2 0 012 2v16l-3-2-3 2-3-2-3 2V5a2 2 0 012-2zm2 5h6M9 12h6M9 16h4',
        cart: 'M3 4h2l2.2 10.5a2 2 0 002 1.5h6.8a2 2 0 001.9-1.4L21 8H7M10 20a1 1 0 100-2 1 1 0 000 2zm7 0a1 1 0 100-2 1 1 0 000 2z',
        chef: 'M7 11h10l-1 9H8l-1-9zM8 8a3 3 0 116 0 3 3 0 113 3H5a3 3 0 113-3z',
        ready: 'M9 12l2 2 4-4M4 6h16M4 18h16M6 6v12m12-12v12',
        takeaway: 'M6 8h12l-1 12H7L6 8zm3 0V6a3 3 0 016 0v2M9 12h6',
        counter: 'M4 10h16v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8zm2-4h12v4H6V6zm3 8h6',
        money: 'M12 8c-2.5 0-4 .9-4 2.2 0 1.5 1.5 2 4 2.3 2.5.3 4 .8 4 2.3 0 1.3-1.5 2.2-4 2.2m0-9V6m0 12v-2M4 6h16v12H4V6z',
    };

    return icons[icon] ?? icons.search;
};

const close = () => {
    open.value = false;
};

const openSearch = () => {
    open.value = true;
};

const clearSearch = () => {
    search.value = '';
};

const handleOutsideClick = (event) => {
    if (!root.value) {
        return;
    }

    if (!root.value.contains(event.target)) {
        close();
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleOutsideClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleOutsideClick);
});
</script>

<template>
    <div ref="root" class="relative w-full max-w-[420px]">
        <div
            class="flex items-center gap-3 rounded-2xl border border-[var(--app-border)] bg-[var(--app-card)] px-4 py-3 shadow-sm transition focus-within:border-[var(--app-primary)] focus-within:ring-4 focus-within:ring-[var(--app-primary)]/10"
        >
            <svg
                class="h-5 w-5 shrink-0 text-[var(--app-primary)]"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
                />
            </svg>

            <input
                v-model="search"
                type="text"
                class="w-full border-0 bg-transparent p-0 text-sm font-semibold text-[var(--app-text)] placeholder:text-[var(--app-muted)] focus:border-0 focus:ring-0"
                placeholder="Buscar acciones, módulos, usuarios..."
                @focus="openSearch"
                @keydown.esc="close"
            />

            <button
                v-if="search"
                type="button"
                class="rounded-lg p-1 text-[var(--app-muted)] transition hover:bg-[var(--app-surface-soft)] hover:text-[var(--app-text)]"
                @click="clearSearch"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <div
            v-if="open"
            class="absolute left-0 top-[calc(100%+0.5rem)] z-50 max-h-[440px] w-[min(92vw,420px)] overflow-y-auto rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-2xl"
        >
            <div class="sticky top-0 z-10 border-b border-[var(--app-border)] bg-[var(--app-card)] px-5 py-4">
                <p class="text-xs font-black uppercase tracking-[0.22em] text-[var(--app-primary)]">
                    Búsqueda rápida
                </p>

                <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                    Accesos disponibles según tu rol: {{ rolesText }}.
                </p>
            </div>

            <div v-if="visibleActions.length" class="py-3">
                <div
                    v-for="(actions, category) in groupedActions"
                    :key="category"
                    class="py-2"
                >
                    <p class="px-5 pb-2 text-[10px] font-black uppercase tracking-[0.24em] text-[var(--app-muted)]">
                        {{ category }}
                    </p>

                    <Link
                        v-for="action in actions"
                        :key="action.key"
                        :href="action.href"
                        class="group flex items-center gap-3 px-5 py-3 transition hover:bg-[var(--app-primary-soft)]"
                        @click="close"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[var(--app-surface-soft)] text-[var(--app-primary)] transition group-hover:scale-105 group-hover:bg-[var(--app-card)]"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    :d="iconPath(action.icon)"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-black text-[var(--app-text)]">
                                {{ action.title }}
                            </p>

                            <p class="mt-0.5 line-clamp-2 text-xs font-semibold text-[var(--app-muted)]">
                                {{ action.description }}
                            </p>
                        </div>

                        <svg
                            class="h-5 w-5 shrink-0 text-[var(--app-muted)] transition group-hover:translate-x-1 group-hover:text-[var(--app-primary)]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </Link>
                </div>
            </div>

            <div v-else class="px-5 py-8 text-center">
                <p class="text-sm font-black text-[var(--app-text)]">
                    No hay resultados
                </p>

                <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                    Intenta buscar productos, categorías, usuarios, bitácora, reservas o pedidos.
                </p>
            </div>
        </div>
    </div>
</template>
