<?php

namespace App\Http\Controllers\Administration;

use App\Exports\AuditLogsExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    private string $timezone = 'America/La_Paz';

    public function index(Request $request): Response
    {
        $this->authorizeAudit();

        return Inertia::render('Administration/AuditLog', [
            ...$this->payload($request),
            'actions' => $this->actions(),
            'modules' => $this->modules(),
            'users' => $this->users(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizeAudit();

        return response()->json($this->payload($request));
    }

    public function exportCsv(Request $request): BinaryFileResponse
    {
        return $this->exportExcel($request);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $this->authorizeAudit();

        return Excel::download(
            new AuditLogsExport($this->exportLogs($request)),
            'bitacora-churrasqueria-roberto.xlsx'
        );
    }

    public function exportTxt(Request $request): StreamedResponse
    {
        $this->authorizeAudit();

        $logs = $this->exportLogs($request);

        return response()->streamDownload(function () use ($logs) {
            echo "BITÁCORA DEL SISTEMA\n";
            echo "CHURRASQUERÍA ROBERTO\n";
            echo "Zona horaria: Bolivia / America La Paz\n";
            echo "Generado: " . now($this->timezone)->format('d/m/Y H:i:s') . "\n";
            echo str_repeat('=', 100) . "\n\n";

            if ($logs->isEmpty()) {
                echo "No hay registros de bitácora para mostrar.\n";
                return;
            }

            foreach ($logs as $index => $log) {
                $number = $index + 1;

                $userName = data_get($log, 'user_name', 'Sistema');
                $userEmail = data_get($log, 'user_email', 'Sin correo');
                $userRole = data_get($log, 'user_role', 'Sin rol');
                $action = data_get($log, 'action', '-');
                $module = data_get($log, 'module', '-');
                $method = data_get($log, 'method', '-');
                $status = data_get($log, 'status_code', '-');
                $routeName = data_get($log, 'route_name', '-');
                $url = data_get($log, 'url', '-');
                $time = data_get($log, 'hora_bolivia', data_get($log, 'time', '-'));
                $date = data_get($log, 'fecha_bolivia', data_get($log, 'date', '-'));
                $ip = data_get($log, 'ip_address', '-');
                $description = data_get($log, 'description', '-');

                echo "REGISTRO N° {$number}\n";
                echo str_repeat('-', 100) . "\n";
                echo "Usuario       : {$userName}\n";
                echo "Correo        : {$userEmail}\n";
                echo "Rol           : {$userRole}\n";
                echo "Acción        : {$action}\n";
                echo "Módulo        : {$module}\n";
                echo "Método/Estado : {$method}:{$status}\n";
                echo "IP            : {$ip}\n";
                echo "Fecha         : {$date}\n";
                echo "Hora          : {$time}\n";
                echo "Ruta          : {$routeName}\n";
                echo "URL           : " . wordwrap($url, 86, "\n                ", true) . "\n";
                echo "Detalle       : " . wordwrap($description, 86, "\n                ", true) . "\n";
                echo str_repeat('=', 100) . "\n\n";
            }
        }, 'bitacora-churrasqueria-roberto.txt', [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorizeAudit();

        $pdf = Pdf::loadView('pdf.audit-logs', [
            'logs' => $this->exportLogs($request)->take(500)->values(),
            'title' => 'Bitácora del sistema',
        ])->setPaper('letter', 'landscape');

        return $pdf->download('bitacora-churrasqueria-roberto.pdf');
    }

    private function payload(Request $request): array
    {
        $perPage = (int) $request->get('per_page', 15);

        if (! in_array($perPage, [15, 30, 50, 100], true)) {
            $perPage = 15;
        }

        $logs = $this->filteredQuery($request)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'logs' => [
                'data' => $logs->getCollection()
                    ->map(fn(AuditLog $log) => $this->mapLog($log))
                    ->values(),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem(),
                ],
            ],
            'filters' => [
                'search' => $request->get('search', ''),
                'action' => $request->get('action', ''),
                'module' => $request->get('module', ''),
                'user_id' => $request->get('user_id', ''),
                'date_from' => $request->get('date_from', ''),
                'date_to' => $request->get('date_to', ''),
                'period' => $request->get('period', 'all'),
                'per_page' => $perPage,
            ],
            'stats' => $this->stats(),
        ];
    }

    private function exportLogs(Request $request): Collection
    {
        return $this->filteredQuery($request)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn(AuditLog $log) => $this->mapLog($log))
            ->values();
    }

    private function filteredQuery(Request $request)
    {
        $search = trim($request->get('search', ''));
        $period = $request->get('period', 'all');

        return AuditLog::query()
            ->select([
                'id',
                'user_id',
                'user_name',
                'user_email',
                'user_role',
                'action',
                'module',
                'description',
                'method',
                'url',
                'route_name',
                'ip_address',
                'status_code',
                'created_at',
                'updated_at',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('user_name', 'ILIKE', "%{$search}%")
                        ->orWhere('user_email', 'ILIKE', "%{$search}%")
                        ->orWhere('user_role', 'ILIKE', "%{$search}%")
                        ->orWhere('action', 'ILIKE', "%{$search}%")
                        ->orWhere('module', 'ILIKE', "%{$search}%")
                        ->orWhere('method', 'ILIKE', "%{$search}%")
                        ->orWhere('url', 'ILIKE', "%{$search}%")
                        ->orWhere('route_name', 'ILIKE', "%{$search}%")
                        ->orWhere('description', 'ILIKE', "%{$search}%")
                        ->orWhere('ip_address', 'ILIKE', "%{$search}%");
                });
            })
            ->when($request->filled('action'), fn($query) => $query->where('action', $request->get('action')))
            ->when($request->filled('module'), fn($query) => $query->where('module', $request->get('module')))
            ->when($request->filled('user_id'), fn($query) => $query->where('user_id', $request->get('user_id')))
            ->when(in_array($period, ['today', 'yesterday', 'month'], true), function ($query) use ($period) {
                [$start, $end] = $this->periodRange($period);

                $query->whereBetween('created_at', [$start, $end]);
            })
            ->when(! in_array($period, ['today', 'yesterday', 'month'], true) && $request->filled('date_from'), function ($query) use ($request) {
                $from = Carbon::parse($request->get('date_from'), $this->timezone)
                    ->startOfDay()
                    ->timezone(config('app.timezone'));

                $query->where('created_at', '>=', $from);
            })
            ->when(! in_array($period, ['today', 'yesterday', 'month'], true) && $request->filled('date_to'), function ($query) use ($request) {
                $to = Carbon::parse($request->get('date_to'), $this->timezone)
                    ->endOfDay()
                    ->timezone(config('app.timezone'));

                $query->where('created_at', '<=', $to);
            });
    }

    private function periodRange(string $period): array
    {
        $now = now($this->timezone);

        if ($period === 'today') {
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
        } elseif ($period === 'yesterday') {
            $start = $now->copy()->subDay()->startOfDay();
            $end = $now->copy()->subDay()->endOfDay();
        } else {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
        }

        return [
            $start->timezone(config('app.timezone')),
            $end->timezone(config('app.timezone')),
        ];
    }

    private function mapLog(AuditLog $log): array
    {
        $date = $log->created_at
            ? $log->created_at->copy()->timezone($this->timezone)
            : null;

        $fechaBolivia = $date?->format('d/m/Y') ?? '-';
        $horaBolivia = $date?->format('H:i:s') ?? '-';
        $fechaHoraBolivia = $date?->format('d/m/Y H:i:s') ?? '-';

        return [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'user_name' => $log->user_name ?? 'Sistema',
            'user_email' => $log->user_email ?? '',
            'user_role' => $log->user_role ?? 'Sin rol',
            'action' => $log->action ?? '-',
            'module' => $log->module ?? '-',
            'description' => $log->description ?? '-',
            'method' => $log->method ?? '-',
            'url' => $log->url ?? '-',
            'route_name' => $log->route_name ?? '-',
            'ip_address' => $log->ip_address ?? '-',
            'status_code' => $log->status_code ?? '-',
            'date' => $fechaBolivia,
            'time' => $horaBolivia,
            'fecha_bolivia' => $fechaBolivia,
            'hora_bolivia' => $horaBolivia,
            'created_at' => $fechaHoraBolivia,
            'created_at_raw' => optional($log->created_at)->toDateTimeString(),
        ];
    }

    private function actions()
    {
        return AuditLog::query()
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    private function modules()
    {
        return AuditLog::query()
            ->whereNotNull('module')
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');
    }

    private function users()
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function stats(): array
    {
        $start = now($this->timezone)
            ->startOfDay()
            ->timezone(config('app.timezone'));

        $end = now($this->timezone)
            ->endOfDay()
            ->timezone(config('app.timezone'));

        return [
            'total' => AuditLog::count(),
            'today' => AuditLog::whereBetween('created_at', [$start, $end])->count(),
            'exports' => AuditLog::where('action', 'EXPORTAR')->count(),
            'edits' => AuditLog::where('action', 'EDITAR')->count(),
            'deletes' => AuditLog::where('action', 'ELIMINAR')->count(),
        ];
    }

    private function authorizeAudit(): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403, 'No tienes permiso para ver la bitácora.');
        }

        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->toArray()
            : [];

        $allowed = count(array_intersect($roles, ['Master', 'Administrador'])) > 0;

        abort_unless($allowed, 403, 'No tienes permiso para ver la bitácora.');
    }
}
