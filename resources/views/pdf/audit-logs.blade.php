<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Bitácora del sistema' }}</title>

    <style>
        @page {
            margin: 22px 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        .header {
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid #ef4444;
        }

        .brand {
            color: #ef4444;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 10px;
            color: #334155;
            margin-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th {
            background: #f3f4f6;
            color: #111827;
            font-weight: bold;
            font-size: 8px;
            padding: 6px 4px;
            border: 1px solid #d1d5db;
            text-align: left;
            vertical-align: top;
        }

        td {
            font-size: 8px;
            padding: 6px 4px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        .col-id {
            width: 4%;
        }

        .col-usuario {
            width: 14%;
        }

        .col-rol {
            width: 9%;
        }

        .col-accion {
            width: 9%;
        }

        .col-modulo {
            width: 9%;
        }

        .col-metodo {
            width: 6%;
        }

        .col-ruta {
            width: 33%;
        }

        .col-fecha {
            width: 8%;
        }

        .col-hora {
            width: 8%;
        }

        .user-name {
            display: block;
            font-weight: bold;
            color: #111827;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .user-email {
            display: block;
            color: #64748b;
            font-size: 7px;
            margin-top: 2px;
            word-break: break-all;
            overflow-wrap: anywhere;
        }

        .action-badge {
            color: #b91c1c;
            font-weight: bold;
            font-size: 7px;
            word-break: break-word;
        }

        .route-name {
            display: block;
            font-weight: bold;
            color: #111827;
            margin-bottom: 3px;
            line-height: 1.25;
            word-break: break-word;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .route-url {
            display: block;
            color: #64748b;
            font-size: 7px;
            line-height: 1.25;
            word-break: break-all;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .empty {
            padding: 18px;
            text-align: center;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="brand">Churrasquería Roberto</div>
        <div class="subtitle">{{ $title ?? 'Bitácora del sistema' }}</div>
        <div class="subtitle">Zona horaria: Bolivia / America La Paz</div>
        <div class="subtitle">Generado: {{ now('America/La_Paz')->format('d/m/Y H:i:s') }}</div>
    </div>

    <table>
        <colgroup>
            {{-- <col class="col-id"> --}}
            <col class="col-usuario">
            <col class="col-rol">
            <col class="col-accion">
            <col class="col-modulo">
            <col class="col-metodo">
            <col class="col-ruta">
            <col class="col-fecha">
            <col class="col-hora">
        </colgroup>

        <thead>
            <tr>
                {{-- <th>ID</th> --}}
                <th>Usuario</th>
                <th>Rol</th>
                <th>Acción</th>
                <th>Módulo</th>
                <th>Método</th>
                <th>Ruta</th>
                <th>Hora</th>
                <th>Fecha</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($logs as $log)
                @php
                    // $id = data_get($log, 'id', '-');

                    $userName = data_get($log, 'user_name') ?? (data_get($log, 'usuario_nombre') ?? 'Sistema');

                    $userEmail = data_get($log, 'user_email') ?? (data_get($log, 'usuario_email') ?? null);

                    $role = data_get($log, 'user_role') ?? (data_get($log, 'rol') ?? 'Sin rol');

                    $action = data_get($log, 'action') ?? (data_get($log, 'accion') ?? '-');

                    $module = data_get($log, 'module') ?? (data_get($log, 'modulo') ?? '-');

                    $method = data_get($log, 'method') ?? (data_get($log, 'metodo') ?? '-');

                    $routeName =
                        data_get($log, 'route_name') ??
                        (data_get($log, 'ruta_nombre') ?? (data_get($log, 'ruta') ?? '-'));

                    $url = data_get($log, 'url') ?? (data_get($log, 'full_url') ?? null);

                    $horaBolivia = data_get($log, 'hora_bolivia') ?? (data_get($log, 'time') ?? '-');
                    $fechaBolivia = data_get($log, 'fecha_bolivia') ?? (data_get($log, 'date') ?? '-');
                @endphp

                <tr>
                    {{-- <td>{{ $id }}</td> --}}

                    <td>
                        <span class="user-name">{{ $userName }}</span>

                        @if (!empty($userEmail))
                            <span class="user-email">{{ $userEmail }}</span>
                        @endif
                    </td>

                    <td>{{ $role }}</td>

                    <td>
                        <span class="action-badge">{{ strtoupper($action) }}</span>
                    </td>

                    <td>{{ $module }}</td>

                    <td>{{ $method }}</td>

                    <td>
                        <span class="route-name">{{ $routeName }}</span>

                        @if (!empty($url))
                            <span class="route-url">{{ $url }}</span>
                        @endif
                    </td>

                    <td>{{ $horaBolivia }}</td>
                    <td>{{ $fechaBolivia }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">
                        No hay registros de bitácora para mostrar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
