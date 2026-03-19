<?php

namespace App\EventListener;

use App\Service\CartMerger;
use Symfony\Component\HttpFoundation\RequestStack;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class JwtLoginListener
{
    public function __construct(
        private CartMerger $cartMerger,
        private RequestStack $requestStack
    ) {}

    /**
     * 🔐 Événement déclenché après un login réussi (JWT)
     *
     * 👉 Objectif :
     * Fusionner le panier session (invité) avec le panier DB (utilisateur connecté)
     *
     * ⚠️ ATTENTION :
     * Selon le contexte (JWT, API stateless), la session peut être différente
     * de celle utilisée côté front → le panier peut être vide ici
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        // 👤 Récupération de l'utilisateur authentifié
        $user = $event->getUser();

        if (!$user) {
            // ❌ sécurité : aucun user → on sort
            return;
        }

        // 🌐 Récupération de la requête HTTP courante
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            // ❌ cas rare : pas de requête disponible
            return;
        }

        // 🍪 Récupération de la session Symfony (panier invité)
        $session = $request->getSession();

        if (!$session) {
            // ❌ pas de session → rien à merger
            return;
        }

        /**
         * 🧪 DEBUG TEMPORAIRE
         *
         * 👉 Permet de vérifier si le panier session existe au moment du login
         *
         * ⚠️ À SUPPRIMER en production
         */
        dump('SESSION CART AVANT MERGE', $session->get('cart'));

        /**
         * 🔥 FUSION DU PANIER
         *
         * 👉 Si la session contient des produits :
         * - ils sont transférés vers la DB
         * - les quantités sont cumulées si besoin
         * - la session est ensuite vidée
         *
         * ⚠️ Si la session est vide ici :
         * → le merge ne fera rien (cas fréquent avec JWT stateless)
         */
        $this->cartMerger->merge($user, $session);
    }
}