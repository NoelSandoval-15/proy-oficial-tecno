<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Sales_Note;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PagoFacilService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.pagofacil.base_url'), '/');
    }

    public function getAccessToken(): string
    {
        $this->validateCredentials();

        return Cache::remember('pagofacil_access_token', now()->addMinutes(30), function () {
            $response = Http::timeout(30)
                ->withHeaders([
                    'tcTokenService' => config('services.pagofacil.token_service'),
                    'tcTokenSecret' => config('services.pagofacil.token_secret'),
                    'Response-Language' => 'es',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . '/login');

            if (!$response->successful()) {
                throw new RuntimeException('No se pudo autenticar con PagoFácil. Código HTTP: ' . $response->status());
            }

            $data = $response->json();

            if ((int) data_get($data, 'error', 1) !== 0) {
                throw new RuntimeException(data_get($data, 'message', 'PagoFácil rechazó la autenticación.'));
            }

            $token = data_get($data, 'values.accessToken');

            if (!$token) {
                throw new RuntimeException('PagoFácil no devolvió accessToken.');
            }

            return $token;
        });
    }

    public function clearToken(): void
    {
        Cache::forget('pagofacil_access_token');
    }

    public function listEnabledServices(): array
    {
        $response = Http::timeout(30)
            ->withToken($this->getAccessToken())
            ->withHeaders([
                'Response-Language' => 'es',
                'Accept' => 'application/json',
            ])
            ->post($this->baseUrl . '/list-enabled-services');

        if ($response->status() === 401) {
            $this->clearToken();

            $response = Http::timeout(30)
                ->withToken($this->getAccessToken())
                ->withHeaders([
                    'Response-Language' => 'es',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . '/list-enabled-services');
        }

        if (!$response->successful()) {
            throw new RuntimeException('No se pudieron listar los métodos QR habilitados. Código HTTP: ' . $response->status());
        }

        $data = $response->json();

        if ((int) data_get($data, 'error', 1) !== 0) {
            throw new RuntimeException(data_get($data, 'message', 'PagoFácil no devolvió métodos QR habilitados.'));
        }

        return $data;
    }

    public function resolvePaymentMethod(): int
    {
        $services = $this->listEnabledServices();

        $values = collect(data_get($services, 'values', []));

        if ($values->isEmpty()) {
            throw new RuntimeException('No hay métodos de pago QR habilitados para este comercio.');
        }

        $bobService = $values->first(function ($item) {
            return strtoupper((string) data_get($item, 'currencyName')) === 'BOB';
        });

        $service = $bobService ?: $values->first();

        $paymentMethodId = data_get($service, 'paymentMethodId');

        if (!$paymentMethodId) {
            throw new RuntimeException('No se encontró paymentMethodId en los servicios habilitados.');
        }

        return (int) $paymentMethodId;
    }

    public function generateQr(Sales_Note $salesNote, Payment $payment): array
    {
        $salesNote->loadMissing([
            'users_client.profile',
            'details.product',
        ]);

        if (!$salesNote->isDelivered()) {
            throw new RuntimeException('Solo se puede generar QR para ventas con estado Entregado.');
        }

        if ($salesNote->isPaid()) {
            throw new RuntimeException('Esta venta ya está pagada.');
        }

        $paymentNumber = $payment->payment_number ?: $this->buildPaymentNumber($salesNote);

        $payload = [
            'paymentMethod' => $this->resolvePaymentMethod(),
            'clientName' => $this->clientFullName($salesNote),
            'documentType' => 1,
            'documentId' => $this->clientDocument($salesNote),
            'phoneNumber' => $this->clientPhone($salesNote),
            'email' => $this->clientEmail($salesNote),
            'paymentNumber' => $paymentNumber,
            'amount' => round((float) $salesNote->total_price, 2),
            'currency' => 2,
            'clientCode' => (string) ($salesNote->users_client_id ?: $salesNote->id),
            'callbackUrl' => config('services.pagofacil.callback_url') ?: url('/api/pagofacil/callback'),
            'orderDetail' => $this->buildOrderDetail($salesNote),
        ];
        // $payload = [
        //     'paymentMethod' => $this->resolvePaymentMethod(),
        //     'clientName' => $this->clientFullName($salesNote),
        //     'documentType' => 1,
        //     'documentId' => $this->clientDocument($salesNote),
        //     'phoneNumber' => $this->clientPhone($salesNote),
        //     'email' => $this->clientEmail($salesNote),
        //     'paymentNumber' => $paymentNumber,
        //     'amount' => round((float) $salesNote->total_price, 2),
        //     'currency' => 2,
        //     'clientCode' => (string) ($salesNote->users_client_id ?: $salesNote->id),
        //     'callbackUrl' => url('/api/pagofacil/callback'),
        //     'orderDetail' => $this->buildOrderDetail($salesNote),
        // ];

        $response = Http::timeout(30)
            ->withToken($this->getAccessToken())
            ->withHeaders([
                'Response-Language' => 'es',
                'Accept' => 'application/json',
            ])
            ->post($this->baseUrl . '/generate-qr', $payload);

        if ($response->status() === 401) {
            $this->clearToken();

            $response = Http::timeout(30)
                ->withToken($this->getAccessToken())
                ->withHeaders([
                    'Response-Language' => 'es',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . '/generate-qr', $payload);
        }

        if (!$response->successful()) {
            throw new RuntimeException('No se pudo generar el QR en PagoFácil. Código HTTP: ' . $response->status());
        }

        $data = $response->json();

        if ((int) data_get($data, 'error', 1) !== 0) {
            throw new RuntimeException(data_get($data, 'message', 'PagoFácil rechazó la generación del QR.'));
        }

        if (!data_get($data, 'values.qrBase64')) {
            throw new RuntimeException('PagoFácil no devolvió el QR en base64.');
        }

        return [
            'payment_number' => $paymentNumber,
            'payload' => $payload,
            'response' => $data,
        ];
    }

    public function queryTransaction(Payment $payment): array
    {
        if (!$payment->payment_number && !$payment->pagofacil_transaction_id) {
            throw new RuntimeException('El pago no tiene identificador para consultar en PagoFácil.');
        }

        $payload = $payment->pagofacil_transaction_id
            ? ['pagofacilTransactionId' => $payment->pagofacil_transaction_id]
            : ['companyTransactionId' => $payment->payment_number];

        $response = Http::timeout(30)
            ->withToken($this->getAccessToken())
            ->withHeaders([
                'Response-Language' => 'es',
                'Accept' => 'application/json',
            ])
            ->post($this->baseUrl . '/query-transaction', $payload);

        if ($response->status() === 401) {
            $this->clearToken();

            $response = Http::timeout(30)
                ->withToken($this->getAccessToken())
                ->withHeaders([
                    'Response-Language' => 'es',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . '/query-transaction', $payload);
        }

        if (!$response->successful()) {
            throw new RuntimeException('No se pudo consultar la transacción en PagoFácil. Código HTTP: ' . $response->status());
        }

        return $response->json();
    }

    public function isPaidStatus(mixed $status): bool
    {
        return in_array((string) $status, [
            '2',
            'paid',
            'PAID',
            'PAGADO',
            'pagado',
            'Pagado',
        ], true);
    }

    public function isCancelledStatus(mixed $status): bool
    {
        return in_array((string) $status, [
            '4',
            'cancelled',
            'CANCELLED',
            'ANULADO',
            'Anulado',
            'anulado',
        ], true);
    }

    public function isRevertedStatus(mixed $status): bool
    {
        return in_array((string) $status, [
            '3',
            'reverted',
            'REVERTED',
            'REVERTIDO',
            'Revertido',
            'revertido',
        ], true);
    }

    private function validateCredentials(): void
    {
        if (!config('services.pagofacil.token_service')) {
            throw new RuntimeException('Falta PAGOFACIL_TOKEN_SERVICE en el archivo .env.');
        }

        if (!config('services.pagofacil.token_secret')) {
            throw new RuntimeException('Falta PAGOFACIL_TOKEN_SECRET en el archivo .env.');
        }
    }

    private function buildPaymentNumber(Sales_Note $salesNote): string
    {
        return 'SN-' . $salesNote->id . '-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
    }

    private function clientFullName(Sales_Note $salesNote): string
    {
        $client = $salesNote->users_client;
        $profile = $client?->profile;

        if ($profile) {
            return trim($profile->name . ' ' . $profile->last_name);
        }

        return $client?->name ?: 'Cliente Mostrador';
    }

    private function clientDocument(Sales_Note $salesNote): string
    {
        $client = $salesNote->users_client;
        $profile = $client?->profile;

        return (string) ($profile?->ci ?: $client?->id ?: $salesNote->id);
    }

    private function clientPhone(Sales_Note $salesNote): string
    {
        $client = $salesNote->users_client;
        $profile = $client?->profile;

        return (string) ($profile?->telephone ?: '70000000');
    }

    private function clientEmail(Sales_Note $salesNote): string
    {
        $client = $salesNote->users_client;

        return $client?->email ?: 'cliente@churrasqueria.local';
    }

    private function buildOrderDetail(Sales_Note $salesNote): array
    {
        $details = $salesNote->details;

        if ($details->isEmpty()) {
            return [
                [
                    'serial' => 1,
                    'product' => 'Venta #' . $salesNote->id,
                    'quantity' => 1,
                    'price' => round((float) $salesNote->total_price, 2),
                    'discount' => 0,
                    'total' => round((float) $salesNote->total_price, 2),
                ],
            ];
        }

        return $details->values()->map(function ($detail, int $index) {
            $quantity = (int) $detail->amount;
            $price = round((float) $detail->price_sale, 2);

            return [
                'serial' => $index + 1,
                'product' => $detail->product?->name ?: 'Producto',
                'quantity' => $quantity,
                'price' => $price,
                'discount' => 0,
                'total' => round($quantity * $price, 2),
            ];
        })->toArray();
    }
}
