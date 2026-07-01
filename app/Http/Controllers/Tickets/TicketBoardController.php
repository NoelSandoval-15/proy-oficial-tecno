<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Sales_Note;
use App\Services\Tickets\TicketStatusService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TicketBoardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'status',
            'order_type',
            'date',
            'per_page',
        ]);

        $query = Sales_Note::query()
            ->with([
                'tables:id,name,amount,state',
                'users_client:id,name,email',
                'users_admin:id,name,email',
                'details.product:id,name,price,url_photo,amount',
                'statusHistories.user:id,name,email',
            ])
            ->whereIn('status', Sales_Note::ticketStatuses());

        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? 12;

        if ($perPage === 'all') {
            $perPage = max($query->count(), 1);
        }

        $tickets = $query
            ->latest()
            ->paginate((int) $perPage)
            ->withQueryString()
            ->through(fn ($order) => $this->ticketPayload($order));

        return Inertia::render('Tickets/Kitchen/Index', [
            'tickets' => $tickets,
            'statuses' => Sales_Note::ticketStatuses(),
            'orderTypes' => Sales_Note::orderTypes(),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'order_type' => $filters['order_type'] ?? '',
                'date' => $filters['date'] ?? '',
                'per_page' => $filters['per_page'] ?? '12',
            ],
            'stats' => $this->statsPayload(),
        ]);
    }

    public function show(Sales_Note $ticket)
    {
        $ticket->load([
            'tables:id,name,amount,state',
            'users_client:id,name,email',
            'users_admin:id,name,email',
            'details.product:id,name,price,url_photo,amount',
            'statusHistories.user:id,name,email',
        ]);

        return Inertia::render('Tickets/Kitchen/Show', [
            'ticket' => $this->ticketPayload($ticket, true),
        ]);
    }

    public function changeStatus(
        Request $request,
        Sales_Note $ticket,
        TicketStatusService $ticketStatusService
    ) {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(Sales_Note::ticketStatuses()),
            ],
        ], [
            'status.required' => 'Debe seleccionar un estado.',
            'status.in' => 'El estado seleccionado no es válido.',
        ]);

        if (!$ticketStatusService->canChangeTo($ticket, $data['status'])) {
            return back()->with('error', 'No se puede cambiar el ticket a ese estado desde el estado actual.');
        }

        $ticketStatusService->changeStatus($ticket, $data['status'], auth()->user());

        return back()->with('success', 'Estado del ticket actualizado correctamente.');
    }

    private function applyFilters($query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('id', $search)
                        ->orWhere('order_type', 'ilike', "%{$search}%")
                        ->orWhereHas('users_client', function ($clientQuery) use ($search) {
                            $clientQuery
                                ->where('name', 'ilike', "%{$search}%")
                                ->orWhere('email', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('tables', function ($tableQuery) use ($search) {
                            $tableQuery->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['order_type'] ?? null, function ($query, $orderType) {
                $query->where('order_type', $orderType);
            })
            ->when($filters['date'] ?? null, function ($query, $date) {
                $query->whereDate('date', $date);
            });
    }

    private function statsPayload(): array
    {
        return [
            'total' => Sales_Note::whereIn('status', Sales_Note::ticketStatuses())->count(),
            'pending' => Sales_Note::where('status', Sales_Note::STATUS_PENDING)->count(),
            'preparing' => Sales_Note::where('status', Sales_Note::STATUS_PREPARING)->count(),
            'ready' => Sales_Note::where('status', Sales_Note::STATUS_READY)->count(),
            'delivered_today' => Sales_Note::where('status', Sales_Note::STATUS_DELIVERED)
                ->whereDate('date', now()->toDateString())
                ->count(),
        ];
    }

    private function ticketPayload(Sales_Note $order, bool $includeTimeline = false): array
    {
        $payload = [
            'id' => $order->id,
            'ticket_code' => 'TCK-' . str_pad($order->id, 5, '0', STR_PAD_LEFT),
            'date' => optional($order->date)->format('Y-m-d'),
            'date_formatted' => optional($order->date)->format('d/m/Y'),
            'hour' => $order->hour ? substr($order->hour, 0, 5) : null,
            'total_price' => $order->total_price,
            'status' => $order->status,
            'order_type' => $order->order_type,
            'table' => $order->tables,
            'client' => $order->users_client ? [
                'id' => $order->users_client->id,
                'name' => $order->users_client->name,
                'email' => $order->users_client->email,
                'label' => trim(($order->users_client->name ?? 'Cliente') . ' · ' . ($order->users_client->email ?? 'Sin correo')),
            ] : null,
            'admin' => $order->users_admin,
            'details' => $order->details->map(fn ($detail) => [
                'id' => $detail->id,
                'products_id' => $detail->products_id,
                'amount' => $detail->amount,
                'price_sale' => $detail->price_sale,
                'product' => $detail->product,
            ])->values(),
            'allowed_next_statuses' => app(TicketStatusService::class)->allowedNextStatuses($order),
        ];

        if ($includeTimeline) {
            $payload['timeline'] = $this->timelinePayload($order);
        }

        return $payload;
    }

    private function timelinePayload(Sales_Note $order): array
    {
        $steps = Sales_Note::ticketStatusSteps();

        return $order->statusHistories
            ->map(fn ($history) => [
                'status' => $history->status,
                'title' => $history->title ?? ($steps[$history->status]['title'] ?? $history->status),
                'description' => $history->description,
                'date' => optional($history->created_at)->format('d/m/Y'),
                'hour' => optional($history->created_at)->format('H:i'),
                'user' => $history->user ? [
                    'id' => $history->user->id,
                    'name' => $history->user->name,
                    'email' => $history->user->email,
                ] : null,
            ])
            ->values()
            ->toArray();
    }
}
