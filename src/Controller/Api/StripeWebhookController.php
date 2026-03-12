<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StripeWebhookController extends AbstractController
{
       
    public function __construct(
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')]
        private readonly string $stripeWebhookSecret
    ) {}

    #[Route('/api/stripe/webhook', name: 'api_stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

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

                $sessionId = isset($session->id) ? (string) $session->id : null;
                $paymentStatus = isset($session->payment_status) ? (string) $session->payment_status : null;
                $clientReferenceId = isset($session->client_reference_id) ? (string) $session->client_reference_id : null;
                $metadataOrderId = isset($session->metadata->order_id) ? (string) $session->metadata->order_id : null;
                $paymentIntentId = is_scalar($session->payment_intent ?? null)
                    ? (string) $session->payment_intent
                    : null;

                $logger->info('Stripe checkout session received', [
                    'event_type' => $event->type,
                    'session_id' => $sessionId,
                    'payment_status' => $paymentStatus,
                    'client_reference_id' => $clientReferenceId,
                    'metadata_order_id' => $metadataOrderId,
                    'payment_intent_id' => $paymentIntentId,
                ]);

                $order = $this->findOrderFromStripeSession($session, $orderRepository, $logger);

                if (!$order) {
                    $logger->warning('Webhook: order not found', [
                        'session_id' => $sessionId,
                        'payment_status' => $paymentStatus,
                        'client_reference_id' => $clientReferenceId,
                        'metadata_order_id' => $metadataOrderId,
                        'payment_intent_id' => $paymentIntentId,
                    ]);

                    return new Response('Order not found', Response::HTTP_OK);
                }

                $logger->info('Webhook: order found', [
                    'order_id' => $order->getId(),
                    'current_status' => $order->getStatus(),
                    'db_stripe_session_id' => $order->getStripeSessionId(),
                    'db_payment_intent_id' => $order->getStripePaymentIntentId(),
                ]);

                if ($paymentStatus !== 'paid') {
                    $logger->warning('Webhook: payment_status is not paid', [
                        'order_id' => $order->getId(),
                        'payment_status' => $paymentStatus,
                    ]);

                    return new Response('Payment not paid', Response::HTTP_OK);
                }

                if ($order->getStatus() === Order::STATUS_PAID) {
                    $logger->info('Webhook: order already paid', [
                        'order_id' => $order->getId(),
                    ]);

                    return new Response('Already processed', Response::HTTP_OK);
                }

                $order->setStripeSessionId($sessionId);
                $order->setStripePaymentIntentId($paymentIntentId);
                $order->setStatus(Order::STATUS_PAID);
                $order->setPaidAt(new \DateTimeImmutable());
                $order->setUpdatedAt(new \DateTimeImmutable());

                $em->flush();

                $logger->info('Order updated with stripe session id', [
                    'order_id' => $order->getId(),
                    'stripe_session_id' => $order->getStripeSessionId(),
                ]);

                $logger->info('Webhook: order marked as PAID', [
                    'order_id' => $order->getId(),
                    'stripe_session_id' => $order->getStripeSessionId(),
                    'stripe_payment_intent_id' => $order->getStripePaymentIntentId(),
                ]);

                return new Response('Order marked as PAID', Response::HTTP_OK);

            case 'checkout.session.expired':
                $session = $object;
                $order = $this->findOrderFromStripeSession($session, $orderRepository, $logger);

                if ($order && $order->getStatus() === Order::STATUS_PENDING_PAYMENT) {
                    $order->setStatus(Order::STATUS_CANCELLED);
                    $order->setUpdatedAt(new \DateTimeImmutable());
                    $em->flush();

                    $logger->info('Webhook: order marked as CANCELLED', [
                        'order_id' => $order->getId(),
                    ]);
                }

                return new Response('Session expired handled', Response::HTTP_OK);

            case 'payment_intent.succeeded':
            case 'charge.succeeded':
                $logger->info('Webhook: event ignored for order state', [
                    'event_type' => $event->type,
                ]);

                return new Response('Event ignored for state', Response::HTTP_OK);

            default:
                $logger->info('Webhook: event ignored', [
                    'event_type' => $event->type,
                ]);

                return new Response('Event ignored', Response::HTTP_OK);
        }
    }

    private function findOrderFromStripeSession(
        object $session,
        OrderRepository $orderRepository,
        LoggerInterface $logger
    ): ?Order {
        $sessionId = isset($session->id) ? trim((string) $session->id) : null;
        $metadataOrderId = isset($session->metadata->order_id)
            ? trim((string) $session->metadata->order_id)
            : null;
        $clientReferenceId = isset($session->client_reference_id)
            ? trim((string) $session->client_reference_id)
            : null;
        $paymentIntentId = is_scalar($session->payment_intent ?? null)
            ? trim((string) $session->payment_intent)
            : null;

        $logger->info('findOrderFromStripeSession', [
            'session_id' => $sessionId,
            'metadata_order_id' => $metadataOrderId,
            'client_reference_id' => $clientReferenceId,
            'payment_intent_id' => $paymentIntentId,
        ]);

        if ($sessionId) {
            $order = $orderRepository->findOneByStripeSessionId($sessionId);

            if ($order) {
                $logger->info('Order found by stripeSessionId', [
                    'order_id' => $order->getId(),
                    'stripe_session_id' => $sessionId,
                ]);

                return $order;
            }
        }

        if ($metadataOrderId && ctype_digit($metadataOrderId)) {
            $order = $orderRepository->find((int) $metadataOrderId);

            if ($order) {
                $logger->info('Order found by metadata.order_id', [
                    'order_id' => $order->getId(),
                    'metadata_order_id' => $metadataOrderId,
                ]);

                return $order;
            }
        }

        if ($clientReferenceId && ctype_digit($clientReferenceId)) {
            $order = $orderRepository->find((int) $clientReferenceId);

            if ($order) {
                $logger->info('Order found by client_reference_id', [
                    'order_id' => $order->getId(),
                    'client_reference_id' => $clientReferenceId,
                ]);

                return $order;
            }
        }

        if ($paymentIntentId) {
            $order = $orderRepository->findOneByStripePaymentIntentId($paymentIntentId);

            if ($order) {
                $logger->info('Order found by stripePaymentIntentId', [
                    'order_id' => $order->getId(),
                    'payment_intent_id' => $paymentIntentId,
                ]);

                return $order;
            }
        }

        $logger->warning('findOrderFromStripeSession: no order matched', [
            'session_id' => $sessionId,
            'metadata_order_id' => $metadataOrderId,
            'client_reference_id' => $clientReferenceId,
            'payment_intent_id' => $paymentIntentId,
        ]);

        return null;
    }
}
