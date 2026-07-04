<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Sales_Note;
use App\Services\PagoFacilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $sales = Sales_Note::query()
            ->withPaymentData()
            ->where(function ($query) {
                $query->whereIn('status', [
                    Sales_Note::STATUS_DELIVERED,
                    Sales_Note::STATUS_PAID,
                ])->orWhereHas('payments');
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    if (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search);
                    }

                    $q->orWhereHas('users_client', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%");
                    });

                    $q->orWhereHas('users_client.profile', function ($profileQuery) use ($search) {
                        $profileQuery->where('name', 'ilike', "%{$search}%")
                            ->orWhere('last_name', 'ilike', "%{$search}%")
                            ->orWhereRaw('CAST(ci AS TEXT) ILIKE ?', ["%{$search}%"])
                            ->orWhereRaw('CAST(telephone AS TEXT) ILIKE ?', ["%{$search}%"]);
                    });

                    $q->orWhereHas('latestPayment', function ($paymentQuery) use ($search) {
                        $paymentQuery->where('payment_number', 'ilike', "%{$search}%")
                            ->orWhere('pagofacil_transaction_id', 'ilike', "%{$search}%")
                            ->orWhere('transaction_id', 'ilike', "%{$search}%");
                    });
                });
            })
            ->when($status, function ($query) use ($status) {
                if ($status === 'delivered') {
                    $query->where('status', Sales_Note::STATUS_DELIVERED);
                    return;
                }

                if ($status === 'paid_sale') {
                    $query->where('status', Sales_Note::STATUS_PAID);
                    return;
                }

                if ($status === 'without_payment') {
                    $query->where('status', Sales_Note::STATUS_DELIVERED)
                        ->whereDoesntHave('payments');
                    return;
                }

                $query->whereHas('payments', function ($paymentQuery) use ($status) {
                    $paymentQuery->where('status', $status);
                });
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Payments/Admin/Index', [
            'sales' => $sales,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'summary' => $this->summary(),
            'paymentStatuses' => Payment::statuses(),
            'paymentMethods' => Payment::paymentMethods(),
        ]);
    }

    public function generateQr(Sales_Note $salesNote, PagoFacilService $pagoFacilService)
    {
        $salesNote->loadMissing([
            'users_client.profile',
            'details.product',
            'payments',
        ]);

        if (!$salesNote->isDelivered()) {
            return back()->with('error', 'Solo se puede generar QR para ventas con estado Entregado.');
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

            Log::error('Error al generar QR PagoFácil', [
                'sales_note_id' => $salesNote->id,
                'payment_id' => $payment->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    public function checkStatus(Payment $payment, PagoFacilService $pagoFacilService)
    {
        $payment->loadMissing('salesNote');

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

            return back()->with('info', 'El pago todavía no fue confirmado.');
        } catch (Throwable $e) {
            Log::error('Error al consultar pago PagoFácil', [
                'payment_id' => $payment->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    public function registerManualPayment(Request $request, Sales_Note $salesNote)
    {
        $validated = $request->validate([
            'payment_method' => [
                'required',
                'string',
                Rule::in([
                    Payment::METHOD_CASH,
                    Payment::METHOD_CARD,
                    Payment::METHOD_TRANSFER,
                ]),
            ],
            'amount_received' => ['required', 'numeric', 'min:0.01'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
        ]);

        $salesNote->loadMissing('payments');

        if (!$salesNote->isDelivered()) {
            return back()->with('error', 'Solo se puede registrar pago para ventas Entregadas.');
        }

        if ($salesNote->isPaid() || $salesNote->hasPaidPayment()) {
            return back()->with('error', 'Esta venta ya fue pagada.');
        }

        $total = round((float) $salesNote->total_price, 2);
        $amountReceived = round((float) $validated['amount_received'], 2);

        if ($amountReceived < $total) {
            return back()->with('error', 'El monto recibido no puede ser menor al total de la venta.');
        }

        try {
            DB::transaction(function () use ($salesNote, $validated, $amountReceived, $total) {
                $payment = Payment::create([
                    'sales_note_id' => $salesNote->id,
                    'payment_method' => $validated['payment_method'],
                    'payment_number' => 'MANUAL-' . $salesNote->id . '-' . now()->format('YmdHis'),
                    'status' => Payment::STATUS_PAID,
                    'amount_received' => $amountReceived,
                    'change' => round($amountReceived - $total, 2),
                    'payment_date' => now(),
                    'paid_at' => now(),
                    'transaction_id' => $validated['transaction_id'] ?? null,
                    'generated_by' => auth()->id(),
                ]);

                $salesNote->markAsPaid(auth()->id());
            });

            return back()->with('success', 'Pago registrado correctamente.');
        } catch (Throwable $e) {
            Log::error('Error al registrar pago manual', [
                'sales_note_id' => $salesNote->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo registrar el pago.');
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

    private function summary(): array
    {
        $deliveredSales = Sales_Note::query()
            ->where('status', Sales_Note::STATUS_DELIVERED);

        $paidPayments = Payment::query()
            ->where('status', Payment::STATUS_PAID);

        return [
            'pending_total' => (float) (clone $deliveredSales)->sum('total_price'),
            'pending_count' => (clone $deliveredSales)->count(),
            'paid_today_total' => (float) (clone $paidPayments)->whereDate('paid_at', today())->sum('amount_received'),
            'paid_today_count' => (clone $paidPayments)->whereDate('paid_at', today())->count(),
            'qr_active_count' => Payment::query()
                ->whereIn('status', [
                    Payment::STATUS_PENDING,
                    Payment::STATUS_QR_GENERATED,
                ])
                ->count(),
            'paid_count' => Payment::query()
                ->where('status', Payment::STATUS_PAID)
                ->count(),
        ];
    }
}
