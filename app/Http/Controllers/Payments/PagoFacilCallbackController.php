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
                messageSistema: 'PedidoID no recibido en callback.',
                values: false
            );
        }

        $payment = Payment::query()
            ->with('salesNote')
            ->where('payment_number', $paymentIdentifier)
            ->orWhere('payment_method_transaction_id', $paymentIdentifier)
            ->orWhere('pagofacil_transaction_id', $paymentIdentifier)
            ->orWhere('transaction_id', $paymentIdentifier)
            ->first();

        if (!$payment) {
            Log::warning('Callback PagoFácil: pago no encontrado', [
                'identifier' => $paymentIdentifier,
                'payload' => $payload,
            ]);

            return $this->pagofacilResponse(
                error: 1,
                status: 0,
                message: 'Pago no encontrado.',
                messageMostrar: 1,
                messageSistema: 'No existe un pago registrado con el identificador recibido.',
                values: false
            );
        }

        try {
            $callbackStatus = $this->resolveCallbackStatus($payload);

            DB::transaction(function () use ($payment, $payload, $callbackStatus, $pagoFacilService) {
                $payment->forceFill([
                    'callback_payload' => $payload,
                ])->save();

                if ($payment->isPaid()) {
                    return;
                }

                if ($pagoFacilService->isPaidStatus($callbackStatus)) {
                    $payment->markAsPaid(
                        $payment->salesNote?->total_price,
                        $payload
                    );

                    return;
                }

                if ($pagoFacilService->isRevertedStatus($callbackStatus)) {
                    $payment->forceFill([
                        'status' => Payment::STATUS_REVERTED,
                        'error_message' => 'La transacción fue revertida por PagoFácil.',
                    ])->save();

                    return;
                }

                if ($pagoFacilService->isCancelledStatus($callbackStatus)) {
                    $payment->forceFill([
                        'status' => Payment::STATUS_CANCELLED,
                        'error_message' => 'La transacción fue anulada por PagoFácil.',
                    ])->save();

                    return;
                }

                $payment->forceFill([
                    'error_message' => 'Callback recibido con estado no pagado: ' . ($callbackStatus ?? 'sin estado'),
                ])->save();
            });

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
                'payment_id' => $payment->id,
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
        $identifier = $payload['PedidoID']
            ?? $payload['pedidoID']
            ?? $payload['pedidoId']
            ?? $payload['paymentNumber']
            ?? $payload['companyTransactionId']
            ?? $payload['payment_method_transaction_id']
            ?? $payload['pagofacilTransactionId']
            ?? $payload['transactionId']
            ?? null;

        return $identifier !== null ? (string) $identifier : null;
    }

    private function resolveCallbackStatus(array $payload): mixed
    {
        return $payload['Estado']
            ?? $payload['estado']
            ?? $payload['paymentStatus']
            ?? $payload['status']
            ?? null;
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
