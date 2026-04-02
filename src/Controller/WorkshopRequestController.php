<?php

namespace App\Controller;

use App\Entity\WorkshopRequest;
use App\Entity\WorkshopRequestAttachment;
use App\Entity\WorkshopRequestItem;
use App\Form\WorkshopRequestType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WorkshopRequestController extends AbstractController
{
    #[Route('/test/workshop-request', name: 'app_workshop_request_test')]
    public function test(
        Request $request,
        EntityManagerInterface $entityManager,
        string $workshopRequestUploadDir
    ): Response {
        /*
        |----------------------------------------------------------------------
        | Initialisation de la demande atelier
        |----------------------------------------------------------------------
        */
        $workshopRequest = new WorkshopRequest();
        $workshopRequest->setStatus(WorkshopRequest::STATUS_NEW);
        $workshopRequest->setPriority(WorkshopRequest::PRIORITY_NORMAL);
        $workshopRequest->setSource(WorkshopRequest::SOURCE_MANUAL_ADMIN);

        /*
        |----------------------------------------------------------------------
        | Première ligne produit au chargement initial
        |----------------------------------------------------------------------
        */
        if (!$request->isMethod('POST')) {
            $workshopRequest->addItem(new WorkshopRequestItem());
        }

        /*
        |----------------------------------------------------------------------
        | Création et traitement du formulaire
        |----------------------------------------------------------------------
        */
        $form = $this->createForm(WorkshopRequestType::class, $workshopRequest);
        $form->handleRequest($request);

        /*
        |----------------------------------------------------------------------
        | Soumission valide -> enregistrement
        |----------------------------------------------------------------------
        */
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile[] $uploadedFiles */
            $uploadedFiles = $form->get('attachmentFiles')->getData() ?? [];

            try {
                foreach ($uploadedFiles as $index => $uploadedFile) {
                    if (!$uploadedFile instanceof UploadedFile) {
                        continue;
                    }

                    $now = new \DateTimeImmutable();
                    $year = $now->format('Y');
                    $month = $now->format('m');

                    $targetDirectory = rtrim($workshopRequestUploadDir, '/\\')
                        . DIRECTORY_SEPARATOR . $year
                        . DIRECTORY_SEPARATOR . $month;

                    /*
                    |--------------------------------------------------------------
                    | Création du dossier cible si nécessaire
                    |--------------------------------------------------------------
                    */
                    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                        throw new \RuntimeException(sprintf('Le dossier "%s" n’a pas pu être créé.', $targetDirectory));
                    }

                    /*
                    |--------------------------------------------------------------
                    | Récupération des métadonnées AVANT move()
                    |--------------------------------------------------------------
                    |
                    | Très important :
                    | après move(), le fichier temporaire PHP n’existe plus.
                    | Il ne faut donc plus appeler des méthodes qui risquent
                    | de relire le fichier temporaire (mimeType, size, etc.).
                    |
                    */
                    $originalName = $uploadedFile->getClientOriginalName();

                    // Mime type "client" : plus sûr ici qu'une détection après move()
                    $mimeType = $uploadedFile->getClientMimeType() ?: 'application/octet-stream';

                    $size = $uploadedFile->getSize() ?? 0;

                    $attachmentType = $this->guessAttachmentTypeFromMimeType($mimeType);

                    $extension = $uploadedFile->guessExtension()
                        ?: $uploadedFile->getClientOriginalExtension()
                        ?: 'bin';

                    $storedName = sprintf(
                        'wr_%s_%s_%s.%s',
                        $now->format('Ymd_His'),
                        $index + 1,
                        bin2hex(random_bytes(6)),
                        $extension
                    );

                    /*
                    |--------------------------------------------------------------
                    | Déplacement physique du fichier
                    |--------------------------------------------------------------
                    */
                    $uploadedFile->move($targetDirectory, $storedName);

                    $relativePath = sprintf(
                        'uploads/workshop-requests/%s/%s/%s',
                        $year,
                        $month,
                        $storedName
                    );

                    $fullStoredPath = $targetDirectory . DIRECTORY_SEPARATOR . $storedName;

                    /*
                    |--------------------------------------------------------------
                    | Création de l'entité pièce jointe
                    |--------------------------------------------------------------
                    */
                    $attachment = new WorkshopRequestAttachment();
                    $attachment
                        ->setOriginalName($originalName)
                        ->setStoredName($storedName)
                        ->setPath($relativePath)
                        ->setMimeType($mimeType)
                        ->setSize($size)
                        ->setAttachmentType($attachmentType)
                        ->setPosition($index)
                        ->setIsVisible(true)
                        ->setIsChecked(false);

                    /*
                    |--------------------------------------------------------------
                    | Hash du fichier final déplacé
                    |--------------------------------------------------------------
                    */
                    /*if (is_file($fullStoredPath) && is_readable($fullStoredPath)) {
                        $attachment->setFileHash(hash_file('sha256', $fullStoredPath));
                    }

                    $workshopRequest->addAttachment($attachment);*/
                }

                $entityManager->persist($workshopRequest);
                $entityManager->flush();

                $this->addFlash('success', 'La demande de test a bien été enregistrée.');

                return $this->redirectToRoute('app_workshop_request_test');
            } catch (FileException $exception) {
                $this->addFlash(
                    'danger',
                    'Erreur lors de l’upload d’un fichier : ' . $exception->getMessage()
                );
            } catch (\Throwable $throwable) {
                $this->addFlash(
                    'danger',
                    'Erreur lors de l’enregistrement : ' . $throwable->getMessage()
                );
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

    /**
     * Détermine un type métier simple à partir d'un mime type.
     */
    private function guessAttachmentTypeFromMimeType(?string $mimeType): string
    {
        if ($mimeType === null) {
            return WorkshopRequestAttachment::TYPE_OTHER;
        }

        if (str_starts_with($mimeType, 'image/')) {
            return WorkshopRequestAttachment::TYPE_VISUAL;
        }

        if ($mimeType === 'application/pdf') {
            return WorkshopRequestAttachment::TYPE_DOCUMENT;
        }

        return WorkshopRequestAttachment::TYPE_OTHER;
    }
}