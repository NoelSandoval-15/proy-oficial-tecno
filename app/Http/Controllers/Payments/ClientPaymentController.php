<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Sales_Note;
use App\Services\PagoFacilService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class ClientPaymentController extends Controller
{
    public function index()
    {
        $sales = Sales_Note::query()
            ->with([
                'details.product',
                'latestPayment',
                'paidPayment',
                'activePayment',
            ])
            ->forClient(auth()->id())
            ->where(function ($query) {
                $query->whereIn('status', [
                    Sales_Note::STATUS_DELIVERED,
                    Sales_Note::STATUS_PAID,
                ])->orWhereHas('payments');
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Payments/Client/Index', [
            'sales' => $sales,
            'summary' => [
                'pending_count' => Sales_Note::query()
                    ->forClient(auth()->id())
                    ->where('status', Sales_Note::STATUS_DELIVERED)
                    ->count(),

                'paid_count' => Sales_Note::query()
                    ->forClient(auth()->id())
                    ->where('status', Sales_Note::STATUS_PAID)
                    ->count(),

                'pending_total' => (float) Sales_Note::query()
                    ->forClient(auth()->id())
                    ->where('status', Sales_Note::STATUS_DELIVERED)
                    ->sum('total_price'),
            ],
        ]);
    }

    public function generateQr(Sales_Note $salesNote, PagoFacilService $pagoFacilService)
    {
        $this->ensureClientOwnsSale($salesNote);

        $salesNote->loadMissing([
            'users_client.profile',
            'details.product',
            'payments',
        ]);

        if (!$salesNote->isDelivered()) {
            return back()->with('error', 'Solo puedes pagar ventas con estado Entregado.');
        }

        if ($salesNote->isPaid() || $salesNote->hasPaidPayment()) {
            return back()->with('error', 'Esta venta ya fue pagada.');
        }

        $payment = $this->getOrCreateQrPayment($salesNote);

        if ($payment->isQrGenerated() && $payment->hasQr() && !$payment->isExpired()) {
            return back()->with('info', 'Esta venta ya tiene un QR activo.');
        }

        try {
            $result = $pagoFacilService->generateQr($salesNote, $payment);

            $payment->registerQrResponse(
                $result['response'],
                $result['payment_number']
            );

            return back()->with('success', 'QR generado correctamente.');
        } catch (Throwable $e) {
            $payment->markAsFailed($e->getMessage());

            Log::error('Error al generar QR PagoFácil desde cliente', [
                'sales_note_id' => $salesNote->id,
                'payment_id' => $payment->id,
                'client_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    public function checkStatus(Payment $payment, PagoFacilService $pagoFacilService)
    {
        $payment->loadMissing('salesNote');

        if (!$payment->salesNote || (int) $payment->salesNote->users_client_id !== (int) auth()->id()) {
            abort(403);
        }

        if ($payment->isPaid()) {
            return back()->with('info', 'Este pago ya fue confirmado.');
        }

        try {
            $response = $pagoFacilService->queryTransaction($payment);

            $payment->forceFill([
                'query_payload' => $response,
            ])->save();

            $paymentStatus = data_get($response, 'values.paymentStatus');

            if ($pagoFacilService->isPaidStatus($paymentStatus)) {
                DB::transaction(function () use ($payment, $response) {
                    $amount = (float) data_get(
                        $response,
                        'values.amount',
                        $payment->salesNote?->total_price ?? $payment->amount_received ?? 0
                    );

                    $payment->markAsPaid($amount);
                });

                return back()->with('success', 'Pago confirmado correctamente.');
            }

            if ($pagoFacilService->isRevertedStatus($paymentStatus)) {
                $payment->forceFill([
                    'status' => Payment::STATUS_REVERTED,
                    'error_message' => 'La transacción fue revertida por PagoFácil.',
                ])->save();

                return back()->with('error', 'La transacción fue revertida.');
            }

            if ($pagoFacilService->isCancelledStatus($paymentStatus)) {
                $payment->forceFill([
                    'status' => Payment::STATUS_CANCELLED,
                    'error_message' => 'La transacción fue anulada por PagoFácil.',
                ])->save();

                return back()->with('error', 'La transacción fue anulada.');
            }

            if ($payment->isExpired()) {
                $payment->markAsExpired();

                return back()->with('error', 'El QR expiró. Genera uno nuevo.');
            }

            return back()->with('info', 'Tu pago todavía no fue confirmado.');
        } catch (Throwable $e) {
            Log::error('Error al consultar pago PagoFácil desde cliente', [
                'payment_id' => $payment->id,
                'client_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    private function getOrCreateQrPayment(Sales_Note $salesNote): Payment
    {
        $payment = Payment::query()
            ->where('sales_note_id', $salesNote->id)
            ->whereIn('status', [
                Payment::STATUS_PENDING,
                Payment::STATUS_QR_GENERATED,
                Payment::STATUS_FAILED,
                Payment::STATUS_EXPIRED,
            ])
            ->latest('id')
            ->first();

        if ($payment) {
            if ($payment->status === Payment::STATUS_EXPIRED || $payment->status === Payment::STATUS_FAILED) {
                $payment->forceFill([
                    'status' => Payment::STATUS_PENDING,
                    'qr_base64' => null,
                    'checkout_url' => null,
                    'deep_link' => null,
                    'qr_content_url' => null,
                    'universal_url' => null,
                    'expiration_date' => null,
                    'error_message' => null,
                    'generated_by' => auth()->id(),
                ])->save();
            }

            return $payment;
        }

        return Payment::create([
            'sales_note_id' => $salesNote->id,
            'payment_method' => Payment::METHOD_QR_PAGOFACIL,
            'status' => Payment::STATUS_PENDING,
            'amount_received' => null,
            'change' => 0,
            'payment_date' => null,
            'generated_by' => auth()->id(),
        ]);
    }

    private function ensureClientOwnsSale(Sales_Note $salesNote): void
    {
        if ((int) $salesNote->users_client_id !== (int) auth()->id()) {
            abort(403);
        }
    }
}
