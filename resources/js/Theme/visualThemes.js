export const normalizeThemeName = (name = '') => {
    const text = String(name).toLowerCase().trim();

    if (text === 'administrador') {
        return 'administrador';
    }

    if (text === 'adultos' || text === 'cliente') {
        return 'adultos';
    }

    if (text === 'ni?o' || text === 'nino') {
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
            '--app-shadow': 'rgba(15, 23, 42, 0.10)'
        }
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
            '--app-shadow': 'rgba(0, 0, 0, 0.35)'
        }
    },

    nino: {
        key: 'nino',
        displayName: 'Nino',
        shortName: 'Nino',
        description: 'Experiencia familiar, amigable, verde y divertida.',
        logo: 'Roberto Kids',
        subtitle: 'Menu familiar',
        mode: 'kids',
        variables: {
            '--app-bg': '#f1ffc4',
            '--app-sidebar': '#efffbd',
            '--app-surface': '#dfead8',
            '--app-surface-soft': '#eaffb9',
            '--app-card': '#dce8d7',
            '--app-border': '#c5d6b8',
            '--app-text': '#2f5f63',
            '--app-muted': '#52625a',
            '--app-primary': '#2f6f73',
            '--app-primary-dark': '#275b5e',
            '--app-primary-soft': '#b8e7ee',
            '--app-primary-text': '#2f5f63',
            '--app-accent': '#aee4ee',
            '--app-hero': '#e8fbb3',
            '--app-hero-text': '#2f5f63',
            '--app-shadow': 'rgba(47, 95, 99, 0.18)'
        }
    }
};

export const getVisualTheme = (themeName = 'Administrador') => {
    const key = normalizeThemeName(themeName);

    return visualThemes[key] || visualThemes.administrador;
};
