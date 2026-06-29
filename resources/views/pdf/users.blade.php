<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #ef4444;
            padding-bottom: 12px;
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
            color: #ef4444;
        }

        .subtitle {
            color: #4b5563;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 7px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Churrasquería Roberto</div>
        <div class="subtitle">{{ $title }}</div>
        <div class="subtitle">Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Correo</th>
                <th>CI</th>
                <th>Teléfono</th>
                <th>Rol</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($users as $user)
                @php
                    $profileName = $user['profile']['name'] ?? null;
                    $profileLastName = $user['profile']['last_name'] ?? null;
                    $fullName = trim(($profileName ?? '') . ' ' . ($profileLastName ?? ''));

                    if ($fullName === '') {
                        $fullName = $user['name'];
                    }
                @endphp

                <tr>
                    <td>{{ $user['id'] }}</td>
                    <td>{{ $fullName }}</td>
                    <td>{{ $user['email'] }}</td>
                    <td>{{ $user['profile']['ci'] ?? 'Sin CI' }}</td>
                    <td>{{ $user['profile']['telephone'] ?? 'Sin teléfono' }}</td>
                    <td>{{ $user['roles'][0] ?? 'Sin rol' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
