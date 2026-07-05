<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PagoFacilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PagoFacilCallbackController extends Controller
{
    public function __invoke(Request $request, PagoFacilService $pagoFacilService): JsonResponse
    {
        $payload = $request->all();

        Log::info('Callback PagoFácil recibido', [
            'payload' => $payload,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $paymentIdentifier = $this->resolvePaymentIdentifier($payload);

        if (!$paymentIdentifier) {
            return $this->pagofacilResponse(
                error: 1,
                status: 0,
                message: 'No se recibió identificador de pago.',
                messageMostrar: 1,
                messageSistema: 'No se recibió PedidoID, companyTransactionId ni transactionId.',
                values: false
            );
        }

        try {
            $processed = DB::transaction(function () use ($paymentIdentifier, $payload, $pagoFacilService) {
                $payment = Payment::query()
                    ->with('salesNote')
                    ->where(function ($query) use ($paymentIdentifier) {
                        $query->where('payment_number', $paymentIdentifier)
                            ->orWhere('payment_method_transaction_id', $paymentIdentifier)
                            ->orWhere('pagofacil_transaction_id', $paymentIdentifier)
                            ->orWhere('transaction_id', $paymentIdentifier);
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    Log::warning('Callback PagoFácil: pago no encontrado', [
                        'identifier' => $paymentIdentifier,
                        'payload' => $payload,
                    ]);

                    return false;
                }

                $callbackStatus = $this->resolveCallbackStatus($payload);

                $payment->forceFill([
                    'callback_payload' => $payload,
                ])->save();

                if ($payment->isPaid()) {
                    return true;
                }

                if ($this->payloadSaysPaid($payload) || $pagoFacilService->isPaidStatus($callbackStatus)) {
                    $amount = $this->resolvePaidAmount($payload, $payment);

                    $payment->markAsPaid($amount, $payload);

                    return true;
                }

                if ($pagoFacilService->isRevertedStatus($callbackStatus)) {
                    $payment->forceFill([
                        'status' => Payment::STATUS_REVERTED,
                        'error_message' => 'La transacción fue revertida por PagoFácil.',
                    ])->save();

                    return true;
                }

                if ($pagoFacilService->isCancelledStatus($callbackStatus)) {
                    $payment->forceFill([
                        'status' => Payment::STATUS_CANCELLED,
                        'error_message' => 'La transacción fue anulada por PagoFácil.',
                    ])->save();

                    return true;
                }

                $payment->forceFill([
                    'error_message' => 'Callback recibido con estado no pagado: ' . ($callbackStatus ?? 'sin estado'),
                ])->save();

                return true;
            });

            if (!$processed) {
                return $this->pagofacilResponse(
                    error: 1,
                    status: 0,
                    message: 'Pago no encontrado.',
                    messageMostrar: 1,
                    messageSistema: 'No existe un pago registrado con el identificador recibido.',
                    values: false
                );
            }

            return $this->pagofacilResponse(
                error: 0,
                status: 1,
                message: 'Pago realizado correctamente',
                messageMostrar: 0,
                messageSistema: '',
                values: true
            );
        } catch (Throwable $e) {
            Log::error('Error procesando callback PagoFácil', [
                'identifier' => $paymentIdentifier,
                'payload' => $payload,
                'message' => $e->getMessage(),
            ]);

            return $this->pagofacilResponse(
                error: 1,
                status: 0,
                message: 'No se pudo procesar el pago.',
                messageMostrar: 1,
                messageSistema: $e->getMessage(),
                values: false
            );
        }
    }

    private function resolvePaymentIdentifier(array $payload): ?string
    {
        $identifier = data_get($payload, 'PedidoID')
            ?? data_get($payload, 'pedidoID')
            ?? data_get($payload, 'pedidoId')
            ?? data_get($payload, 'paymentNumber')
            ?? data_get($payload, 'companyTransactionId')
            ?? data_get($payload, 'values.companyTransactionId')
            ?? data_get($payload, 'payment_method_transaction_id')
            ?? data_get($payload, 'pagofacilTransactionId')
            ?? data_get($payload, 'values.pagofacilTransactionId')
            ?? data_get($payload, 'transactionId')
            ?? data_get($payload, 'values.transactionId')
            ?? null;

        return $identifier !== null ? (string) $identifier : null;
    }

    private function resolveCallbackStatus(array $payload): mixed
    {
        return data_get($payload, 'Estado')
            ?? data_get($payload, 'estado')
            ?? data_get($payload, 'paymentStatus')
            ?? data_get($payload, 'values.paymentStatus')
            ?? data_get($payload, 'status')
            ?? null;
    }

    private function payloadSaysPaid(array $payload): bool
    {
        if (array_key_exists('values', $payload) && is_bool($payload['values'])) {
            return $payload['values'] === true;
        }

        if (array_key_exists('values', $payload) && is_string($payload['values'])) {
            return in_array(strtolower($payload['values']), ['true', '1', 'pagado', 'paid'], true);
        }

        return false;
    }

    private function resolvePaidAmount(array $payload, Payment $payment): float
    {
        return (float) (
            data_get($payload, 'amount')
            ?? data_get($payload, 'Monto')
            ?? data_get($payload, 'values.amount')
            ?? $payment->salesNote?->total_price
            ?? $payment->amount_received
            ?? 0
        );
    }

    private function pagofacilResponse(
        int $error,
        int $status,
        string $message,
        int $messageMostrar,
        string $messageSistema,
        bool $values
    ): JsonResponse {
        return response()->json([
            'error' => $error,
            'status' => $status,
            'message' => $message,
            'messageMostrar' => $messageMostrar,
            'messageSistema' => $messageSistema,
            'values' => $values,
        ], 200);
    }
}
