<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditTrail
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $this->record($request, method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null);

        return $response;
    }

    private function record(Request $request, ?int $statusCode): void
    {
        try {
            if (! auth()->check()) {
                return;
            }

            if ($this->shouldSkip($request)) {
                return;
            }

            $user = $request->user();
            $routeName = $request->route()?->getName();

            $action = $this->detectAction($request, $routeName);
            $module = $this->detectModule($routeName, $request->path());

            AuditLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->getRoleNames()->first(),
                'action' => $action,
                'module' => $module,
                'description' => "{$user->name} ejecutó {$action} en {$module}.",
                'method' => $request->method(),
                'url' => substr($request->fullUrl(), 0, 2048),
                'route_name' => $routeName,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status_code' => $statusCode,
                'metadata' => [
                    'path' => $request->path(),
                    'query' => $request->query(),
                    'input' => $this->safeInput($request),
                    'files' => $this->uploadedFiles($request),
                ],
            ]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo registrar la bitácora.', [
                'error' => $exception->getMessage(),
                'route' => $request->route()?->getName(),
                'url' => $request->fullUrl(),
            ]);
        }
    }

    private function shouldSkip(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        $path = $request->path();

        return $routeName === 'administracion.bitacora.data'
            || str_starts_with($path, 'build')
            || str_starts_with($path, 'storage')
            || str_starts_with($path, 'favicon')
            || str_starts_with($path, '_debugbar')
            || str_starts_with($path, 'sanctum');
    }

    private function detectAction(Request $request, ?string $routeName): string
    {
        if ($routeName === 'themes.select') {
            return 'CAMBIAR_TEMA';
        }

        if ($routeName && str_contains($routeName, 'export')) {
            return 'EXPORTAR';
        }

        if ($routeName === 'administracion.usuarios.buscar') {
            return $request->filled('search') || $request->filled('user')
                ? 'BUSCAR'
                : 'VISITAR';
        }

        return match ($request->method()) {
            'POST' => $request->input('_method') === 'patch' ? 'EDITAR' : 'CREAR',
            'PATCH', 'PUT' => 'EDITAR',
            'DELETE' => 'ELIMINAR',
            'GET' => 'VISITAR',
            default => $request->method(),
        };
    }

    private function detectModule(?string $routeName, string $path): string
    {
        if (! $routeName) {
            return $this->humanize($path);
        }

        $knownModules = [
            'dashboard' => 'Dashboard',
            'themes' => 'Tema visual',
            'profile' => 'Perfil',
            'administracion.empleados' => 'Empleados',
            'administracion.clientes' => 'Clientes',
            'administracion.administradores' => 'Administradores',
            'administracion.usuarios.buscar' => 'Buscar usuario',
            'administracion.bitacora' => 'Bitácora',
        ];

        foreach ($knownModules as $prefix => $label) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix . '.')) {
                return $label;
            }
        }

        return collect(explode('.', $routeName))
            ->take(2)
            ->map(fn ($part) => $this->humanize($part))
            ->join(' / ');
    }

    private function humanize(string $value): string
    {
        return str($value)
            ->replace(['/', '-', '_', '.'], ' ')
            ->title()
            ->toString();
    }

    private function safeInput(Request $request): array
    {
        $input = $request->except([
            'password',
            'password_confirmation',
            'current_password',
            'photo',
            '_token',
        ]);

        return Arr::map($input, function ($value) {
            if (is_string($value) && strlen($value) > 500) {
                return substr($value, 0, 500) . '...';
            }

            return $value;
        });
    }

    private function uploadedFiles(Request $request): array
    {
        $files = [];

        foreach ($request->allFiles() as $key => $file) {
            if (is_array($file)) {
                $files[$key] = collect($file)
                    ->map(fn ($item) => $item?->getClientOriginalName())
                    ->values()
                    ->all();

                continue;
            }

            $files[$key] = $file?->getClientOriginalName();
        }

        return $files;
    }
}
