<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class KashierService
{
    private string $merchantId;
    private string $apiKey;
    private string $secretKey;
    private string $mode;
    private string $currency;

    public function __construct()
    {
        $this->merchantId = env('KASHIER_MERCHANT_ID', '');
        $this->apiKey     = env('KASHIER_API_KEY', '');
        $this->secretKey  = env('KASHIER_SECRET_KEY', '');
        $this->mode       = env('KASHIER_MODE', 'test');
        $this->currency   = env('KASHIER_CURRENCY', 'EGP');
    }

    public function generateHash(Order $order, ?string $customerReference = null): string
    {
        $amount   = (string) ceil($order->reservation_amount);
        $orderId  = $order->reference_number;
        $currency = $this->currency;
        $path = "/?payment={$this->merchantId}.{$orderId}.{$amount}.{$currency}";
        if (!empty($customerReference)) {
            $path .= ".{$customerReference}";
        }
        $hash = hash_hmac('sha256', $path, $this->apiKey, false);
        Log::info('Kashier Hash Generation', [
            'merchantId'        => $this->merchantId,
            'orderId'           => $orderId,
            'amount'            => $amount,
            'currency'          => $currency,
            'customerReference' => $customerReference,
            'path'              => $path,
            'secret'            => substr($this->apiKey, 0, 10) . '...',
            'hash'              => $hash,
        ]);
        return $hash;
    }

    /**
     * Validate webhook signature from Kashier
     */
    public function validateSignature(array $data): bool
    {
        if (!isset($data['signature'])) {
            return false;
        }
        $queryString = "";
        $receivedSignature = $data['signature'];
        foreach ($data as $key => $value) {
            if ($key === "signature" || $key === "mode") {
                continue;
            }
            $queryString .= "&" . $key . "=" . $value;
        }
        
        $queryString = ltrim($queryString, '&');
        $signature = hash_hmac('sha256', $queryString, $this->apiKey, false);
        return $signature === $receivedSignature;
    }

    /**
     * Get payment configuration for Kashier iFrame
     */
    public function getPaymentConfig(Order $order): array
    {
        $baseUrl = rtrim(env('APP_URL', 'http://localhost'), '/');
        return [
            'amount'            => (string) ceil($order->reservation_amount),
            'hash'              => $this->generateHash($order),
            'currency'          => (string) $this->currency,
            'orderId'           => (string) $order->reference_number,
            'merchantId'        => (string) $this->merchantId,
            'mode'              => (string) $this->mode,
            'merchantRedirect'  => (string) $baseUrl . '/payment/success',
            'serverWebhook'     => (string) $baseUrl . '/payment/webhook/kashier',
            'failureRedirect'   => (string) $baseUrl . '/payment/failed',
            'description'       => "حجز سيارة "
                . ($order->bookingCarClone->car_brand_name ?? 'N/A') . " "
                . ($order->bookingCarClone->car_model_name ?? 'N/A'),
            'customerReference' => (string) $order->user_id,
            'allowedMethods'    => env('KASHIER_METHODS', 'card,wallet'),
        ];
    }
}
