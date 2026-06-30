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
        .right { text-align: right; }
        .center { text-align: center; }
        .badge { display: inline-block; padding: 3px 7px; border-radius: 8px; font-weight: 800; }
        .ok { background: #d1fae5; color: #047857; }
        .low { background: #fef3c7; color: #92400e; }
        .out { background: #fee2e2; color: #b91c1c; }
        .footer { margin-top: 14px; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Churrasquería Roberto</div>
        <div class="title">{{ $title }}</div>
        <div class="muted">Generado: {{ now('America/La_Paz')->format('d/m/Y H:i:s') }} | Total: {{ $products->count() }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Subcategoría</th>
                <th class="right">Precio Bs</th>
                <th class="center">Cantidad</th>
                <th>Estado</th>
                {{-- <th>Imagen</th> --}}
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                @php
                    $amount = (int) data_get($product, 'amount', 0);
                    $stockClass = $amount <= 0 ? 'out' : ($amount <= 5 ? 'low' : 'ok');
                    $stockLabel = $amount <= 0 ? 'Sin stock' : ($amount <= 5 ? 'Stock bajo' : 'Disponible');
                @endphp
                <tr>
                    <td><strong>{{ data_get($product, 'name', '-') }}</strong></td>
                    <td>{{ data_get($product, 'sub_categorie.categorie.name', 'Sin categoría') }}</td>
                    <td>{{ data_get($product, 'sub_categorie.name', 'Sin subcategoría') }}</td>
                    <td class="right">{{ number_format((float) data_get($product, 'price', 0), 2, '.', ',') }}</td>
                    <td class="center">{{ $amount }}</td>
                    <td><span class="badge {{ $stockClass }}">{{ $stockLabel }}</span></td>
                    {{-- <td>{{ data_get($product, 'url_photo', 'Sin imagen') ?: 'Sin imagen' }}</td> --}}
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="center">No hay productos para mostrar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Reporte generado automáticamente por el sistema de Churrasquería Roberto.
    </div>
</body>
</html>
