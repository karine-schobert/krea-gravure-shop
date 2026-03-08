<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StripeWebhookController extends AbstractController
{
    public function __construct(
        private readonly string $stripeWebhookSecret
    ) {
    }

    #[Route('/api/stripe/webhook', name: 'api_stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        $logger->info('Stripe webhook called', [
            'has_signature' => $sigHeader !== null,
            'webhook_secret_configured' => !empty($this->stripeWebhookSecret),
        ]);

        if (!$sigHeader) {
            return new Response('Missing Stripe-Signature header', Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->stripeWebhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            $logger->error('Stripe webhook invalid payload', [
                'error' => $e->getMessage(),
            ]);

            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException $e) {
            $logger->error('Stripe webhook invalid signature', [
                'error' => $e->getMessage(),
            ]);

            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        $object = $event->data->object;

        switch ($event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                $session = $object;
                $order = $this->findOrderFromStripeSession($session, $orderRepository);

                if (!$order) {
                    $logger->warning('Order not found from Stripe session', [
                        'session_id' => $session->id ?? null,
                        'metadata_order_id' => $session->metadata->order_id ?? null,
                        'client_reference_id' => $session->client_reference_id ?? null,
                    ]);

                    return new Response('Order not found', Response::HTTP_NOT_FOUND);
                }

                if (($session->payment_status ?? null) !== 'paid') {
                    return new Response('Payment not paid', Response::HTTP_OK);
                }

                if ($order->getStatus() === Order::STATUS_PAID) {
                    return new Response('Already processed', Response::HTTP_OK);
                }

                $paymentIntent = $session->payment_intent ?? null;

                $order->setStripeSessionId($session->id ?? null);
                $order->setStripePaymentIntentId(is_string($paymentIntent) ? $paymentIntent : null);
                $order->setStatus(Order::STATUS_PAID);
                $order->setPaidAt(new \DateTimeImmutable());
                $order->setUpdatedAt(new \DateTimeImmutable());

                $em->flush();

                $logger->info('Order marked as PAID', [
                    'order_id' => $order->getId(),
                    'stripe_session_id' => $order->getStripeSessionId(),
                ]);

                return new Response('Order marked as PAID', Response::HTTP_OK);

            case 'checkout.session.expired':
                $session = $object;
                $order = $this->findOrderFromStripeSession($session, $orderRepository);

                if ($order && $order->getStatus() === Order::STATUS_PENDING_PAYMENT) {
                    $order->setStatus(Order::STATUS_CANCELLED);
                    $order->setUpdatedAt(new \DateTimeImmutable());
                    $em->flush();
                }

                return new Response('Session expired handled', Response::HTTP_OK);

            default:
                return new Response('Event ignored', Response::HTTP_OK);
        }
    }

    private function findOrderFromStripeSession(object $session, OrderRepository $orderRepository): ?Order
    {
        $order = null;

        $metadataOrderId = $session->metadata->order_id ?? null;
        if ($metadataOrderId && ctype_digit((string) $metadataOrderId)) {
            $order = $orderRepository->find((int) $metadataOrderId);
        }

        if (!$order && !empty($session->client_reference_id) && ctype_digit((string) $session->client_reference_id)) {
            $order = $orderRepository->find((int) $session->client_reference_id);
        }

        if (!$order && !empty($session->id)) {
            $order = $orderRepository->findOneBy([
                'stripeSessionId' => $session->id,
            ]);
        }

        return $order;
    }
}