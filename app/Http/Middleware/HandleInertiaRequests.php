<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Theme;
use Illuminate\Support\Facades\Cache;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    // public function share(Request $request): array
    // {
    //     return [
    //         ...parent::share($request),
    //         'auth' => [
    //             'user' => $request->user(),
    //         ],
    //     ];
    // }

    public function share(Request $request): array
    {
        $user = $request->user();

        $routeKey = $request->route()?->getName() ?? $request->path();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'themes_id' => $user->themes_id,
                    'roles' => $user->getRoleNames()->values(),
                    'theme' => $user->theme ? [
                        'id' => $user->theme->id,
                        'name' => $user->theme->name,
                    ] : null,
                ] : null,
            ],

            'roles' => $request->user() && method_exists($request->user(), 'getRoleNames')
                ? $request->user()->getRoleNames()->values()
                : [],

            'visualThemes' => fn() => Theme::query()
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(function ($theme) {
                    $normalized = str($theme->name)
                        ->lower()
                        ->ascii()
                        ->trim()
                        ->toString();

                    if (in_array($normalized, ['cliente', 'clientes', 'adulto', 'adultos'], true)) {
                        $key = 'adultos';
                        $name = 'Adultos';
                    } elseif (in_array($normalized, ['nino', 'ninos', 'niño', 'niños'], true)) {
                        $key = 'nino';
                        $name = 'Niño';
                    } else {
                        $key = 'administrador';
                        $name = 'Administrador';
                    }

                    return [
                        'id' => $theme->id,
                        'key' => $key,
                        'name' => $name,
                    ];
                })
                ->unique('key')
                ->values(),

            'routeVisits' => [
                'current' => Cache::get('route_visits:' . $routeKey, 0),
                'last' => Cache::get('route_visits_last:' . $routeKey),
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'info' => fn() => $request->session()->get('info'),
                'warning' => fn() => $request->session()->get('warning'),
            ],
        ];
    }
}
