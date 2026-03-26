<?php

namespace App\Form;

use App\Entity\ProductOffer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductOfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // =========================================================
            // INFORMATIONS COMMERCIALES PRINCIPALES
            // =========================================================

            ->add('title', TextType::class, [
                'label' => 'Titre de l’offre',
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Ex. À l’unité, Lot de 4, Offre Noël',
                ],
                'help' => 'Nom visible dans l’administration et sur la fiche produit.',
            ])

            ->add('saleType', ChoiceType::class, [
                'label' => 'Type d’offre',
                'choices' => ProductOffer::getSaleTypeChoices(),
                'placeholder' => false,
                'required' => true,
                'data' => ProductOffer::SALE_TYPE_UNIT,
                'help' => 'Définit la logique commerciale de cette offre.',
            ])

            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité incluse',
                'required' => true,
                'empty_data' => '1',
                'attr' => [
                    'min' => 1,
                    'placeholder' => '1',
                ],
                'help' => 'Nombre de pièces vendues dans cette offre.',
            ])

            ->add('priceCents', IntegerType::class, [
                'label' => 'Prix (en centimes)',
                'required' => true,
                'empty_data' => '0',
                'attr' => [
                    'min' => 0,
                    'placeholder' => '990',
                ],
                'help' => 'Exemple : 990 = 9,90 €',
            ])

            ->add('isActive', CheckboxType::class, [
                'label' => 'Offre active',
                'required' => false,
                'data' => true,
                'help' => 'Une offre inactive reste enregistrée mais n’est pas proposée au client.',
            ])

            ->add('position', IntegerType::class, [
                'label' => 'Position d’affichage',
                'required' => false,
                'empty_data' => '0',
                'attr' => [
                    'min' => 0,
                    'placeholder' => '0',
                ],
                'help' => 'Permet de trier les offres sur la fiche produit.',
            ])

            // =========================================================
            // PERSONNALISATION
            // =========================================================

            ->add('isCustomizable', CheckboxType::class, [
                'label' => 'Cette offre est personnalisable',
                'required' => false,
                'help' => 'Coche cette case si le client doit ou peut saisir un texte.',
            ])

            ->add('customizationLabel', TextType::class, [
                'label' => 'Libellé du champ de personnalisation',
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Ex. Prénom, Texte à graver, Mot à inscrire',
                ],
                'help' => 'Texte affiché au client au-dessus du champ.',
            ])

            ->add('customizationPlaceholder', TextType::class, [
                'label' => 'Placeholder du champ',
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Ex. Charlotte',
                ],
                'help' => 'Texte d’exemple visible dans le champ de saisie.',
            ])

            ->add('customizationMaxLength', IntegerType::class, [
                'label' => 'Nombre maximum de caractères',
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'min' => 1,
                    'placeholder' => '12',
                ],
                'help' => 'Limite facultative du texte saisi par le client.',
            ])

            ->add('isCustomizationRequired', CheckboxType::class, [
                'label' => 'Personnalisation obligatoire',
                'required' => false,
                'help' => 'À cocher seulement si le client doit obligatoirement remplir le champ.',
            ])

            // =========================================================
            // VALIDITÉ / TEMPORALITÉ
            // =========================================================

            ->add('startsAt', DateTimeType::class, [
                'label' => 'Début de validité',
                'required' => false,
                'widget' => 'single_text',
                'help' => 'Laisse vide si l’offre est disponible immédiatement.',
            ])

            ->add('endsAt', DateTimeType::class, [
                'label' => 'Fin de validité',
                'required' => false,
                'widget' => 'single_text',
                'help' => 'Laisse vide si l’offre n’a pas de date de fin.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductOffer::class,
        ]);
    }
}