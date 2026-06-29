<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackRouteVisits
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $request->route()) {
            $routeName = $request->route()->getName() ?? $request->path();

            Cache::increment('route_visits:' . $routeName);
            Cache::put('route_visits_last:' . $routeName, now()->format('d/m/Y H:i:s'));
        }

        return $response;
    }
}
