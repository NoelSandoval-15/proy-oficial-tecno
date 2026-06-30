<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; }
        .header { border-bottom: 3px solid #ef4444; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { font-size: 20px; font-weight: 800; color: #ef4444; }
        .title { font-size: 15px; font-weight: 800; margin-top: 4px; }
        .muted { color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #ef4444; color: #ffffff; padding: 8px; border: 1px solid #e5e7eb; text-align: left; }
        td { padding: 7px; border: 1px solid #e5e7eb; vertical-align: top; }
        tr:nth-child(even) td { background: #f9fafb; }
        .center { text-align: center; }
        .footer { margin-top: 14px; font-size: 9px; color: #6b7280; }
        .item { margin-bottom: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Churrasquería Roberto</div>
        <div class="title">{{ $title }}</div>
        <div class="muted">Generado: {{ now('America/La_Paz')->format('d/m/Y H:i:s') }} | Total: {{ $categories->count() }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th class="center">Subcategorías</th>
                <th class="center">Productos</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($categories as $category)
                @php
                    $subCategories = collect(data_get($category, 'sub_categories', []));
                    $productsCount = $subCategories->sum('products_count');
                @endphp
                <tr>
                    <td><strong>{{ data_get($category, 'name', '-') }}</strong></td>
                    <td class="center">{{ data_get($category, 'sub_categories_count', $subCategories->count()) }}</td>
                    <td class="center">{{ $productsCount }}</td>
                    <td>
                        @forelse ($subCategories as $subCategory)
                            <div class="item">• {{ data_get($subCategory, 'name', '-') }} — {{ data_get($subCategory, 'products_count', 0) }} productos</div>
                        @empty
                            Sin subcategorías
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center">No hay categorías para mostrar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Reporte generado automáticamente por el sistema de Churrasquería Roberto.
    </div>
</body>
</html>
