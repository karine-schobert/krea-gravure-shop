<?php

namespace App\Service;

use App\Entity\Order;
use Psr\Log\LoggerInterface;
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
        private readonly LoggerInterface $logger,
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

        $params = [
            'mode' => 'payment',
            'customer_email' => $order->getEmail(),
            'line_items' => $lineItems,
            'success_url' => rtrim($this->frontendUrl, '/') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => rtrim($this->frontendUrl, '/') . '/checkout/cancel',

            // Liaison directe session Stripe -> commande Symfony
            'client_reference_id' => (string) $order->getId(),

            // Metadata sur la session Checkout
            'metadata' => [
                'order_id' => (string) $order->getId(),
            ],

            // Metadata aussi sur le PaymentIntent
            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => (string) $order->getId(),
                ],
            ],
        ];

        $this->logger->info('Stripe checkout session create params', [
            'order_id' => $order->getId(),
            'customer_email' => $order->getEmail(),
            'client_reference_id' => $params['client_reference_id'],
            'metadata_order_id' => $params['metadata']['order_id'] ?? null,
            'success_url' => $params['success_url'],
            'cancel_url' => $params['cancel_url'],
            'line_items_count' => count($lineItems),
        ]);

        $session = $stripe->checkout->sessions->create($params);

        $this->logger->info('Stripe checkout session created', [
            'order_id' => $order->getId(),
            'session_id' => $session->id ?? null,
            'session_client_reference_id' => $session->client_reference_id ?? null,
            'session_metadata_order_id' => isset($session->metadata->order_id)
                ? (string) $session->metadata->order_id
                : null,
            'session_url' => $session->url ?? null,
        ]);

        return $session;
    }
}