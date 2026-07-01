<?php

namespace App\Services\Tickets;

use App\Models\Sales_Note;
use App\Models\SalesNoteStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketStatusService
{
    public function register(
        Sales_Note $order,
        string $status,
        ?User $user = null,
        ?string $customDescription = null
    ): SalesNoteStatusHistory {
        $steps = Sales_Note::ticketStatusSteps();

        $title = $steps[$status]['title'] ?? 'Estado actualizado';
        $description = $customDescription ?? ($steps[$status]['description'] ?? 'El estado del pedido fue actualizado.');

        return SalesNoteStatusHistory::create([
            'sales_notes_id' => $order->id,
            'users_id' => $user?->id,
            'status' => $status,
            'title' => $title,
            'description' => $description,
        ]);
    }

    public function registerInitialStatus(Sales_Note $order, ?User $user = null): SalesNoteStatusHistory
    {
        $alreadyExists = $order->statusHistories()
            ->where('status', $order->status)
            ->exists();

        if ($alreadyExists) {
            return $order->statusHistories()
                ->where('status', $order->status)
                ->first();
        }

        return $this->register($order, $order->status, $user);
    }

    public function changeStatus(Sales_Note $order, string $newStatus, ?User $user = null): Sales_Note
    {
        return DB::transaction(function () use ($order, $newStatus, $user) {
            $oldStatus = $order->status;

            if ($oldStatus === $newStatus) {
                return $order;
            }

            $order->update([
                'status' => $newStatus,
                'users_admin_id' => $order->users_admin_id ?? $user?->id,
            ]);

            $this->register($order->fresh(), $newStatus, $user);

            return $order->fresh([
                'tables',
                'users_client',
                'users_admin',
                'details.product',
                'statusHistories.user',
            ]);
        });
    }

    public function allowedNextStatuses(Sales_Note $order): array
    {
        return match ($order->status) {
            Sales_Note::STATUS_PENDING => [
                Sales_Note::STATUS_PREPARING,
                Sales_Note::STATUS_CANCELLED,
            ],
            Sales_Note::STATUS_PREPARING => [
                Sales_Note::STATUS_READY,
                Sales_Note::STATUS_CANCELLED,
            ],
            Sales_Note::STATUS_READY => [
                Sales_Note::STATUS_DELIVERED,
                Sales_Note::STATUS_CANCELLED,
            ],
            default => [],
        };
    }

    public function canChangeTo(Sales_Note $order, string $newStatus): bool
    {
        if ($order->status === $newStatus) {
            return true;
        }

        return in_array($newStatus, $this->allowedNextStatuses($order), true);
    }
}
