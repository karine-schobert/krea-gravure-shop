<?php

namespace App\Controller;

use App\Entity\WorkshopRequest;
use App\Entity\WorkshopRequestItem;
use App\Form\WorkshopRequestType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WorkshopRequestController extends AbstractController
{
    #[Route('/test/workshop-request', name: 'app_workshop_request_test')]
    public function test(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /*
        |----------------------------------------------------------------------
        | Création d'une nouvelle demande atelier
        |----------------------------------------------------------------------
        |
        | On initialise ici l'entité principale avec les champs internes
        | qui ne sont pas saisis directement par l'utilisateur dans le formulaire.
        |
        */
        $workshopRequest = new WorkshopRequest();

        $workshopRequest->setStatus(WorkshopRequest::STATUS_NEW);
        $workshopRequest->setPriority(WorkshopRequest::PRIORITY_NORMAL);
        $workshopRequest->setSource(WorkshopRequest::SOURCE_MANUAL_ADMIN);
        $workshopRequest->setSubmittedAt(new \DateTimeImmutable());

        /*
        |----------------------------------------------------------------------
        | Première ligne produit affichée uniquement au chargement initial
        |----------------------------------------------------------------------
        |
        | En GET, on ajoute une ligne vide pour que la collection items
        | affiche au moins un bloc dans le formulaire.
        |
        | On évite de le faire en POST pour ne pas perturber l'hydratation
        | automatique de la collection par Symfony.
        |
        */
        if (!$request->isMethod('POST')) {
            $workshopRequest->addItem(new WorkshopRequestItem());
        }

        /*
        |----------------------------------------------------------------------
        | Création du formulaire
        |----------------------------------------------------------------------
        */
        $form = $this->createForm(WorkshopRequestType::class, $workshopRequest);

        /*
        |----------------------------------------------------------------------
        | Traitement de la requête
        |----------------------------------------------------------------------
        |
        | Symfony récupère les données POST et hydrate automatiquement
        | l'entité WorkshopRequest ainsi que les items de la collection.
        |
        */
        $form->handleRequest($request);

        /*
        |----------------------------------------------------------------------
        | Soumission valide -> persistance en base
        |----------------------------------------------------------------------
        |
        | Si le formulaire est soumis et valide, on enregistre la demande
        | principale. Les items seront persistés automatiquement grâce au
        | cascade persist défini dans l'entité WorkshopRequest.
        |
        */
        if ($form->isSubmitted() && $form->isValid()) {
            /*
            |------------------------------------------------------------------
            | Sécurisation de la relation parent -> enfants
            |------------------------------------------------------------------
            |
            | On rattache explicitement chaque item à la demande principale,
            | même si addItem() le fait déjà normalement.
            |
            */
            foreach ($workshopRequest->getItems() as $item) {
                $item->setWorkshopRequest($workshopRequest);
            }

            /*
            |------------------------------------------------------------------
            | Référence temporaire de test
            |------------------------------------------------------------------
            |
            | On génère une référence simple tant que la génération automatique
            | n'est pas encore totalement déplacée dans l'entité.
            |
            */
            $workshopRequest->setReference(
                'WR-' . (new \DateTimeImmutable())->format('Ymd-His') . '-' . random_int(100, 999)
            );

            try {
                $entityManager->persist($workshopRequest);
                $entityManager->flush();

                $this->addFlash('success', 'La demande de test a bien été enregistrée.');

                /*
                |--------------------------------------------------------------
                | Redirection post-submit
                |--------------------------------------------------------------
                |
                | Évite une double soumission si l'utilisateur recharge la page.
                |
                */
                return $this->redirectToRoute('app_workshop_request_test');
            } catch (\Throwable $e) {
                /*
                |--------------------------------------------------------------
                | Gestion simple d'erreur pour la phase de test
                |--------------------------------------------------------------
                */
                $this->addFlash('danger', 'Erreur lors de l’enregistrement : ' . $e->getMessage());
            }
        }

        /*
        |----------------------------------------------------------------------
        | Affichage du formulaire
        |----------------------------------------------------------------------
        */
        return $this->render('workshop_request/test.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}