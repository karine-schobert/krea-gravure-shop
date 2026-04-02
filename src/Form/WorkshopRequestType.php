<?php

namespace App\Form;

use App\Entity\WorkshopRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkshopRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /*
            |------------------------------------------------------------------
            | Informations demandeur
            |------------------------------------------------------------------
            |
            | Ces champs correspondent au bloc principal côté visiteur.
            | On met required=true uniquement sur les champs réellement
            | obligatoires pour un test de persistance propre.
            |
            */

            ->add('customerType', ChoiceType::class, [
                'label' => 'Vous êtes',
                'choices' => [
                    'Particulier' => WorkshopRequest::CUSTOMER_TYPE_INDIVIDUAL,
                    'Entreprise' => WorkshopRequest::CUSTOMER_TYPE_COMPANY,
                    'Association' => WorkshopRequest::CUSTOMER_TYPE_ASSOCIATION,
                ],
                'placeholder' => 'Choisir un profil',
                'required' => true,
            ])

            ->add('fullName', TextType::class, [
                'label' => 'Nom / prénom',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex : Charlotte Dupont',
                ],
            ])

            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'required' => true,
                'attr' => [
                    'placeholder' => 'contact@email.fr',
                ],
            ])

            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : 06 12 34 56 78',
                ],
            ])

            ->add('preferredContactMethod', ChoiceType::class, [
                'label' => 'Préférence de contact',
                'choices' => [
                    'E-mail' => 'email',
                    'Téléphone' => 'phone',
                ],
                'placeholder' => 'Choisir un mode de contact',
                'required' => false,
            ])

            /*
            |------------------------------------------------------------------
            | Informations structure
            |------------------------------------------------------------------
            |
            | companyName n'est pas toujours obligatoire.
            | L'entité gère déjà la règle métier :
            | obligatoire seulement pour entreprise / association.
            |
            */

            ->add('companyName', TextType::class, [
                'label' => 'Nom de l’entreprise / association',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Krea Gravure / Association des écoles',
                ],
            ])

            /*
            |------------------------------------------------------------------
            | Nature de la demande
            |------------------------------------------------------------------
            |
            | requestType, subject et message sont obligatoires côté entité.
            | On les garde obligatoires ici aussi pour éviter tout décalage.
            |
            */

            ->add('requestType', ChoiceType::class, [
                'label' => 'Type de demande',
                'choices' => [
                    'Demande d’information' => WorkshopRequest::REQUEST_TYPE_INFORMATION,
                    'Demande personnalisée' => WorkshopRequest::REQUEST_TYPE_CUSTOM_REQUEST,
                    'Demande professionnelle' => WorkshopRequest::REQUEST_TYPE_PROFESSIONAL_REQUEST,
                    'Demande association' => WorkshopRequest::REQUEST_TYPE_ASSOCIATION_REQUEST,
                    'Demande événement' => WorkshopRequest::REQUEST_TYPE_EVENT_REQUEST,
                    'Précommande' => WorkshopRequest::REQUEST_TYPE_PREORDER,
                    'Demande de devis' => WorkshopRequest::REQUEST_TYPE_QUOTE_REQUEST,
                ],
                'placeholder' => 'Choisir un type de demande',
                'required' => true,
            ])

            ->add('subject', TextType::class, [
                'label' => 'Sujet',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex : Porte-clés personnalisés pour un événement',
                ],
            ])

            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'required' => true,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Décris ton projet, les quantités approximatives, la personnalisation souhaitée, le contexte, la date éventuelle, etc.',
                ],
            ])

            /*
            |------------------------------------------------------------------
            | Lignes de besoin produit
            |------------------------------------------------------------------
            |
            | On réintroduit ici la collection items.
            | C’est ce bloc qui permettra de faire passer correctement
            | les demandes avancées :
            | - professional_request
            | - quote_request
            | - preorder
            | - event_request
            | etc.
            |
            | by_reference = false est important pour que Symfony appelle bien
            | addItem() / removeItem() sur l’entité parente si ces méthodes
            | existent dans WorkshopRequest.
            |
            */

            ->add('items', CollectionType::class, [
                'label' => false,
                'entry_type' => WorkshopRequestItemType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'prototype' => true,
            ])

            /*
            |------------------------------------------------------------------
            | Champs de contexte utiles côté public
            |------------------------------------------------------------------
            |
            | Ces champs ne sont pas obligatoires en permanence.
            | L'entité gère déjà les cas métier :
            | - eventDate obligatoire si requestType = event_request
            | - d'autres champs peuvent rester facultatifs pour la V1
            |
            */

            ->add('requiresQuote', CheckboxType::class, [
                'label' => 'Je souhaite recevoir un devis',
                'required' => false,
            ])

            ->add('desiredDate', DateType::class, [
                'label' => 'Date souhaitée',
                'required' => false,
                'widget' => 'single_text',
            ])

            ->add('eventDate', DateType::class, [
                'label' => 'Date de l’événement',
                'required' => false,
                'widget' => 'single_text',
            ])

            ->add('eventName', TextType::class, [
                'label' => 'Nom de l’événement',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Mariage, marché de Noël, événement d’entreprise',
                ],
            ])

            ->add('deliveryMethod', ChoiceType::class, [
                'label' => 'Mode souhaité',
                'choices' => [
                    'Retrait' => WorkshopRequest::DELIVERY_METHOD_PICKUP,
                    'Livraison' => WorkshopRequest::DELIVERY_METHOD_DELIVERY,
                    'À définir ensemble' => WorkshopRequest::DELIVERY_METHOD_TO_DISCUSS,
                ],
                'placeholder' => 'Choisir un mode',
                'required' => false,
            ])

            ->add('budgetNotes', TextType::class, [
                'label' => 'Budget indicatif',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : autour de 100 € / petit budget / à discuter',
                ],
            ])

            ->add('deadlineNotes', TextType::class, [
                'label' => 'Contraintes de délai',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : besoin avant le 15 mai',
                ],
            ])

            /*
            |------------------------------------------------------------------
            | Consentement RGPD
            |------------------------------------------------------------------
            |
            | Ce champ est obligatoire côté entité.
            | Même si on le préremplit à true dans le contrôleur de test,
            | on le garde visible ici pour la future vraie version publique.
            |
            */

            ->add('consentRgpd', CheckboxType::class, [
                'label' => 'J’accepte que mes données soient utilisées pour être recontacté(e) au sujet de ma demande.',
                'required' => true,
            ])

            /*
            |------------------------------------------------------------------
            | Important pour la V1 de persistance
            |------------------------------------------------------------------
            |
            | On n’ajoute toujours pas :
            | - source
            | - ipAddress
            | - userAgent
            | - status / priority / adminNotes / etc.
            |
            | Pourquoi :
            | - source est fixé proprement dans le contrôleur
            | - status / priority relèvent du back office
            | - le bloc admin reste hors formulaire public
            |
            */;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkshopRequest::class,
        ]);
    }
}
