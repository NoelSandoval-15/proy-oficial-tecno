export const normalizeThemeName = (name = '') => {
    const text = String(name)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();

    if (text === 'administrador' || text === 'admin') {
        return 'administrador';
    }

    if (text === 'adultos' || text === 'adulto' || text === 'cliente' || text === 'clientes') {
        return 'adultos';
    }

    if (text === 'nino' || text === 'ninos' || text === 'niño' || text === 'niños') {
        return 'nino';
    }

    return 'administrador';
};

export const visualThemes = {
    administrador: {
        key: 'administrador',
        displayName: 'Administrador',
        shortName: 'Admin',
        description: 'Panel de control, inventario, ventas y gestion general.',
        logo: 'Churrasqueria Roberto',
        subtitle: 'Administracion',
        mode: 'admin',
        variables: {
            '--app-bg': '#f7f9fb',
            '--app-sidebar': '#ffffff',
            '--app-surface': '#ffffff',
            '--app-surface-soft': '#f1f5f9',
            '--app-card': '#ffffff',
            '--app-border': '#e2e8f0',
            '--app-text': '#0f172a',
            '--app-muted': '#57534e',
            '--app-primary': '#ef4444',
            '--app-primary-dark': '#b91c1c',
            '--app-primary-soft': '#fee2e2',
            '--app-primary-text': '#991b1b',
            '--app-accent': '#fb923c',
            '--app-hero': '#111111',
            '--app-hero-text': '#ffffff',
            '--app-shadow': 'rgba(15, 23, 42, 0.10)',
        },
    },

    adultos: {
        key: 'adultos',
        displayName: 'Adultos',
        shortName: 'Adultos',
        description: 'Experiencia elegante, oscura y premium para clientes adultos.',
        logo: 'Roberto',
        subtitle: 'Churrasqueria Premium',
        mode: 'adult',
        variables: {
            '--app-bg': '#0f1211',
            '--app-sidebar': '#111514',
            '--app-surface': '#1d2321',
            '--app-surface-soft': '#262c2a',
            '--app-card': '#1a1f1d',
            '--app-border': '#343b38',
            '--app-text': '#f8fafc',
            '--app-muted': '#d6d3d1',
            '--app-primary': '#f5c400',
            '--app-primary-dark': '#c99700',
            '--app-primary-soft': '#3a3214',
            '--app-primary-text': '#facc15',
            '--app-accent': '#d97706',
            '--app-hero': '#070908',
            '--app-hero-text': '#fff7ed',
            '--app-shadow': 'rgba(0, 0, 0, 0.35)',
        },
    },

    nino: {
        key: 'nino',
        displayName: 'Niño',
        shortName: 'Niño',
        description: 'Experiencia familiar, colorida, divertida y llamativa para niños.',
        logo: 'Roberto Kids',
        subtitle: 'Menú divertido',
        mode: 'kids',
        variables: {
            '--app-bg': '#fff7d6',
            '--app-sidebar': '#fff0f6',
            '--app-surface': '#ffffff',
            '--app-surface-soft': '#ffe8f1',
            '--app-card': '#ffffff',
            '--app-border': '#ffc6dc',

            '--app-text': '#3b245c',
            '--app-muted': '#7c5a91',

            '--app-primary': '#ff4fa3',
            '--app-primary-dark': '#d93682',
            '--app-primary-soft': '#ffd6ea',
            '--app-primary-text': '#9d174d',

            '--app-accent': '#38bdf8',
            '--app-hero': '#7c3aed',
            '--app-hero-text': '#ffffff',

            '--app-shadow': 'rgba(255, 79, 163, 0.20)',
        },
    },
};

export const getVisualTheme = (themeName = 'Administrador') => {
    const key = normalizeThemeName(themeName);

    return visualThemes[key] || visualThemes.administrador;
};
