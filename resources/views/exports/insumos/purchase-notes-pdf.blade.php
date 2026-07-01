<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 11px;
        }

        .header {
            border-bottom: 3px solid #ef4444;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .brand {
            font-size: 20px;
            font-weight: 800;
            color: #ef4444;
        }

        .title {
            font-size: 15px;
            font-weight: 800;
            margin-top: 4px;
        }

        .muted {
            color: #6b7280;
            font-size: 10px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 12px;
        }

        .card-title {
            font-size: 13px;
            font-weight: 800;
            color: #111827;
        }

        .total {
            color: #ef4444;
            font-weight: 800;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th {
            background: #ef4444;
            color: #ffffff;
            padding: 7px;
            border: 1px solid #e5e7eb;
            text-align: left;
        }

        td {
            padding: 6px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        tr:nth-child(even) td {
            background: #f9fafb;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .footer {
            margin-top: 14px;
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="brand">Churrasquería Roberto</div>
        <div class="title">{{ $title }}</div>
        <div class="muted">
            Generado: {{ now('America/La_Paz')->format('d/m/Y H:i:s') }}
            | Compras: {{ $purchaseNotes->count() }}
        </div>
    </div>

    @forelse ($purchaseNotes as $purchaseNote)
        <div class="card">
            <div class="card-title">
                Compra #{{ $purchaseNote->id }}
                <span class="total">| Total Bs.
                    {{ number_format((float) $purchaseNote->total_price, 2, '.', ',') }}</span>
            </div>

            <p class="muted">
                Fecha: {{ $purchaseNote->date }}
                | Hora: {{ $purchaseNote->hour }}
                | Proveedor: {{ $purchaseNote->suppliers?->name ?? 'Sin proveedor' }}
                | Administrador: {{ $purchaseNote->users?->name ?? 'Sin usuario' }}
            </p>

            <table>
                <thead>
                    <tr>
                        <th>Insumo</th>
                        <th class="center">Cantidad</th>
                        <th class="right">Subtotal Bs</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($purchaseNote->details_purchases as $detail)
                        <tr>
                            <td><strong>{{ $detail->insumos?->name ?? 'Insumo eliminado' }}</strong></td>
                            <td class="center">{{ $detail->amount }}</td>
                            <td class="right">{{ number_format((float) $detail->price_purchase, 2, '.', ',') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="center">Sin detalle registrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @empty
        <p>No hay compras para mostrar.</p>
    @endforelse

    <div class="footer">
        Reporte generado automáticamente por el sistema de Churrasquería Roberto.
    </div>
</body>

</html>
