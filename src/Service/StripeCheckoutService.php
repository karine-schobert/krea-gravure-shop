<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\ApiRequestor;
use Stripe\Checkout\Session;
use Stripe\HttpClient\CurlClient;
use Stripe\StripeClient;
use Symfony\Component\HttpKernel\KernelInterface;

class StripeCheckoutService
{
    public function __construct(
        private readonly string $stripeSecretKey,
        private readonly string $frontendUrl,
        private readonly KernelInterface $kernel,
    ) {
    }

    public function createCheckoutSession(Order $order): Session
    {
        if ($this->kernel->getEnvironment() === 'dev') {
            $httpClient = new CurlClient([
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);

            ApiRequestor::setHttpClient($httpClient);
        }

        $stripe = new StripeClient($this->stripeSecretKey);

        $lineItems = [];

        foreach ($order->getItems() as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $order->getCurrency(),
                    'product_data' => [
                        'name' => $item->getProductTitle(),
                    ],
                    'unit_amount' => $item->getUnitPriceCents(),
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        return $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $order->getEmail(),
            'line_items' => $lineItems,
            'success_url' => $this->frontendUrl . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->frontendUrl . '/checkout/cancel',
            'client_reference_id' => (string) $order->getId(),
            'metadata' => [
                'order_id' => (string) $order->getId(),
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => (string) $order->getId(),
                ],
            ],
        ]);
    }
}