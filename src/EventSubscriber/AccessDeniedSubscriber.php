<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class AccessDeniedSubscriber implements EventSubscriberInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        // ✅ attrape les 2 types de 403 possibles
        if (!$e instanceof \Symfony\Component\Security\Core\Exception\AccessDeniedException
            && !$e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // ✅ 1) /admin => redirection HTML
        if (str_starts_with($path, '/admin')) {
            if ($path === '/access-denied') {
                return;
            }

            $event->setResponse(new \Symfony\Component\HttpFoundation\RedirectResponse(
                $this->urlGenerator->generate('app_access_denied')
            ));
            return;
        }

        // ✅ 2) /api/private => JSON 403 propre (pas de page debug)
        if (str_starts_with($path, '/api/private')) {
            $event->setResponse(new \Symfony\Component\HttpFoundation\JsonResponse([
                'error' => 'forbidden',
                'message' => 'Access denied',
            ], 403));
            return;
        }
    }
}