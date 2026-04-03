<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class WorkshopRequestAttachmentUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /*
            |--------------------------------------------------------------------------
            | Fichier à uploader
            |--------------------------------------------------------------------------
            |
            | Champ non mappé : il ne correspond pas directement à une propriété
            | Doctrine. On récupérera ensuite le fichier dans le contrôleur
            | pour créer une vraie entité WorkshopRequestAttachment.
            |
            | Formats autorisés pour une vraie demande atelier :
            | - PDF
            | - JPG / JPEG
            | - PNG
            | - WEBP
            |
            */
            ->add('file', FileType::class, [
                'label' => 'Fichier',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez envoyer un fichier PDF, JPG, PNG ou WEBP.',
                    ]),
                ],
                'attr' => [
                    'accept' => '.pdf,.jpg,.jpeg,.png,.webp',
                ],
                'help' => 'Formats acceptés : PDF, JPG, PNG, WEBP. Taille maximale : 10 Mo.',
            ])

            /*
            |--------------------------------------------------------------------------
            | Type métier du fichier
            |--------------------------------------------------------------------------
            |
            | Sert à classer le fichier uploadé : logo, visuel, document, etc.
            |
            | Ce champ reste non mappé car c’est le contrôleur qui décidera
            | ensuite comment hydrater l’entité WorkshopRequestAttachment.
            |
            */
            ->add('attachmentType', ChoiceType::class, [
                'label' => 'Type de fichier',
                'mapped' => false,
                'required' => false,
                'choices' => [
                    'Visuel' => 'visual',
                    'Logo' => 'logo',
                    'Document' => 'document',
                    'Inspiration' => 'inspiration',
                    'Autre' => 'other',
                ],
                'placeholder' => 'Choisir...',
            ]);
    }
}