<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sales_Detail;
use App\Models\Sales_Note;
use App\Services\Tickets\TicketStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createOrder(array $data): Sales_Note
    {
        return DB::transaction(function () use ($data) {
            $items = $this->prepareItems($data['items']);

            $order = Sales_Note::create([
                'hour' => now()->format('H:i:s'),
                'date' => now()->toDateString(),
                'total_price' => collect($items)->sum('price_sale'),
                'status' => $data['status'] ?? Sales_Note::STATUS_PENDING,
                'order_type' => $data['order_type'],
                'users_admin_id' => $data['users_admin_id'] ?? null,
                'users_client_id' => $data['users_client_id'] ?? null,
                'tables_id' => $data['tables_id'] ?? null,
                'reservations_id' => $data['reservations_id'] ?? null,
            ]);

            foreach ($items as $item) {
                Sales_Detail::create([
                    'sales_notes_id' => $order->id,
                    'products_id' => $item['product_id'],
                    'amount' => $item['amount'],
                    'price_sale' => $item['price_sale'],
                ]);

                Product::where('id', $item['product_id'])
                    ->decrement('amount', $item['amount']);
            }

            $this->registerTicketHistoryIfNeeded($order, $order->status);

            return $order->load([
                'details.product',
                'users_admin',
                'users_client',
                'tables',
                'statusHistories.user',
            ]);
        });
    }

    public function updateOrder(Sales_Note $order, array $data): Sales_Note
    {
        return DB::transaction(function () use ($order, $data) {
            $oldStatus = $order->status;

            $this->restoreStock($order);

            $order->details()->delete();

            $items = $this->prepareItems($data['items']);

            $order->update([
                'total_price' => collect($items)->sum('price_sale'),
                'status' => $data['status'] ?? $order->status,
                'order_type' => $data['order_type'],
                'users_admin_id' => $data['users_admin_id'] ?? $order->users_admin_id,
                'users_client_id' => $data['users_client_id'] ?? null,
                'tables_id' => $data['tables_id'] ?? null,
                'reservations_id' => $data['reservations_id'] ?? null,
            ]);

            foreach ($items as $item) {
                Sales_Detail::create([
                    'sales_notes_id' => $order->id,
                    'products_id' => $item['product_id'],
                    'amount' => $item['amount'],
                    'price_sale' => $item['price_sale'],
                ]);

                Product::where('id', $item['product_id'])
                    ->decrement('amount', $item['amount']);
            }

            if ($oldStatus !== $order->status) {
                $this->registerTicketHistoryIfNeeded($order->fresh(), $order->status);
            }

            return $order->load([
                'details.product',
                'users_admin',
                'users_client',
                'tables',
                'statusHistories.user',
            ]);
        });
    }

    public function cancelOrder(Sales_Note $order): void
    {
        DB::transaction(function () use ($order) {
            if ($order->status === Sales_Note::STATUS_CANCELLED) {
                return;
            }

            $this->restoreStock($order);

            $order->update([
                'status' => Sales_Note::STATUS_CANCELLED,
            ]);

            $this->registerTicketHistoryIfNeeded($order->fresh(), Sales_Note::STATUS_CANCELLED);
        });
    }

    private function prepareItems(array $items): array
    {
        $prepared = [];

        foreach ($items as $item) {
            $productId = (int) ($item['products_id'] ?? $item['product_id'] ?? 0);
            $amount = (int) ($item['amount'] ?? 0);

            if ($productId <= 0 || $amount <= 0) {
                continue;
            }

            $product = Product::query()
                ->lockForUpdate()
                ->find($productId);

            if (!$product) {
                throw ValidationException::withMessages([
                    'items' => 'Uno de los productos seleccionados no existe.',
                ]);
            }

            if ($product->amount < $amount) {
                throw ValidationException::withMessages([
                    'items' => "No hay stock suficiente para {$product->name}. Disponible: {$product->amount}.",
                ]);
            }

            $prepared[] = [
                'product_id' => $product->id,
                'amount' => $amount,
                'price_sale' => $product->price * $amount,
            ];
        }

        if (count($prepared) === 0) {
            throw ValidationException::withMessages([
                'items' => 'Debe seleccionar al menos un producto.',
            ]);
        }

        return $prepared;
    }

    private function restoreStock(Sales_Note $order): void
    {
        $order->loadMissing('details');

        foreach ($order->details as $detail) {
            Product::where('id', $detail->products_id)
                ->increment('amount', $detail->amount);
        }
    }

    private function registerTicketHistoryIfNeeded(Sales_Note $order, string $status): void
    {
        if (!in_array($status, Sales_Note::ticketStatuses(), true)) {
            return;
        }

        $lastHistory = $order->statusHistories()
            ->latest()
            ->first();

        if ($lastHistory && $lastHistory->status === $status) {
            return;
        }

        app(TicketStatusService::class)->register(
            $order,
            $status,
            auth()->user()
        );
    }
}
