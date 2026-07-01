<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Sales_Note;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientTicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Sales_Note::query()
            ->with([
                'tables:id,name,amount,state',
                'users_admin:id,name,email',
                'details.product:id,name,price,url_photo,amount',
                'statusHistories.user:id,name,email',
            ])
            ->where('users_client_id', auth()->id())
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($order) => $this->ticketPayload($order));

        return Inertia::render('Tickets/Client/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function show(Sales_Note $ticket)
    {
        if ($ticket->users_client_id !== auth()->id()) {
            abort(403);
        }

        $ticket->load([
            'tables:id,name,amount,state',
            'users_admin:id,name,email',
            'users_client:id,name,email',
            'details.product:id,name,price,url_photo,amount',
            'statusHistories.user:id,name,email',
        ]);

        return Inertia::render('Tickets/Client/Show', [
            'ticket' => $this->ticketPayload($ticket, true),
        ]);
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
            'admin' => $order->users_admin,
            'details' => $order->details->map(fn ($detail) => [
                'id' => $detail->id,
                'products_id' => $detail->products_id,
                'amount' => $detail->amount,
                'price_sale' => $detail->price_sale,
                'product' => $detail->product,
            ])->values(),
        ];

        if ($includeTimeline) {
            $payload['timeline'] = $this->timelinePayload($order);
        }

        return $payload;
    }

    private function timelinePayload(Sales_Note $order): array
    {
        $steps = Sales_Note::ticketStatusSteps();
        $ranks = Sales_Note::ticketStatusRank();

        $baseStatuses = [
            Sales_Note::STATUS_PENDING,
            Sales_Note::STATUS_PREPARING,
            Sales_Note::STATUS_READY,
            Sales_Note::STATUS_DELIVERED,
        ];

        if ($order->status === Sales_Note::STATUS_CANCELLED) {
            $baseStatuses[] = Sales_Note::STATUS_CANCELLED;
        }

        $currentRank = $ranks[$order->status] ?? 0;
        $isCancelled = $order->status === Sales_Note::STATUS_CANCELLED;

        return collect($baseStatuses)
            ->map(function ($status) use ($order, $steps, $ranks, $currentRank, $isCancelled) {
                $history = $order->statusHistories
                    ->where('status', $status)
                    ->first();

                $rank = $ranks[$status] ?? 0;

                $completedByRank = !$isCancelled
                    && $status !== Sales_Note::STATUS_CANCELLED
                    && $rank <= $currentRank;

                $completed = $history || $completedByRank || $status === $order->status;

                return [
                    'status' => $status,
                    'title' => $history?->title ?? ($steps[$status]['title'] ?? $status),
                    'description' => $history?->description ?? ($steps[$status]['description'] ?? null),
                    'is_active' => $status === $order->status,
                    'is_completed' => (bool) $completed,
                    'date' => optional($history?->created_at)->format('d/m/Y'),
                    'hour' => optional($history?->created_at)->format('H:i'),
                    'user' => $history?->user ? [
                        'id' => $history->user->id,
                        'name' => $history->user->name,
                        'email' => $history->user->email,
                    ] : null,
                ];
            })
            ->values()
            ->toArray();
    }
}
