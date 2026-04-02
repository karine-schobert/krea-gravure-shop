<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

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
            */
            ->add('file', FileType::class, [
                'label' => 'Fichier',
                'mapped' => false,
                'required' => false,
            ])

            /*
            |--------------------------------------------------------------------------
            | Type métier du fichier
            |--------------------------------------------------------------------------
            |
            | Sert à classer le fichier uploadé : logo, visuel, document, etc.
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
            ])
        ;
    }
}