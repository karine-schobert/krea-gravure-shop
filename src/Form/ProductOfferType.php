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
            // Titre commercial de l'offre
            ->add('title', TextType::class, [
                'label' => 'Titre de l’offre',
                'required' => true,
            ])

            // Type de vente
            ->add('saleType', ChoiceType::class, [
                'label' => 'Type d’offre',
                'choices' => [
                    'À l’unité' => 'unit',
                    'Lot' => 'bundle',
                    'Saisonnier' => 'seasonal',
                    'Offre spéciale' => 'special',
                    'Collection complète' => 'full_collection',
                ],
                'placeholder' => 'Choisir un type',
                'required' => true,
            ])

            // Quantité incluse dans l'offre
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'required' => true,
                'empty_data' => '1',
            ])

            // Prix de l'offre en centimes
            ->add('priceCents', IntegerType::class, [
                'label' => 'Prix (en centimes)',
                'required' => true,
                'help' => 'Exemple : 990 = 9,90 €',
            ])

            // Offre personnalisable ou non
            ->add('isCustomizable', CheckboxType::class, [
                'label' => 'Personnalisable',
                'required' => false,
            ])

            // Libellé du champ affiché au client
            ->add('customizationLabel', TextType::class, [
                'label' => 'Libellé personnalisation',
                'required' => false,
                'help' => 'Exemple : Prénom, Texte à graver, Mot à inscrire',
            ])

            // Placeholder du champ affiché au client
            ->add('customizationPlaceholder', TextType::class, [
                'label' => 'Placeholder personnalisation',
                'required' => false,
                'help' => 'Exemple : Ex. Charlotte',
            ])

            // Longueur max autorisée
            ->add('customizationMaxLength', IntegerType::class, [
                'label' => 'Longueur max',
                'required' => false,
                'help' => 'Exemple : 10, 12, 20',
            ])

            // Personnalisation obligatoire ou non
            ->add('isCustomizationRequired', CheckboxType::class, [
                'label' => 'Personnalisation obligatoire',
                'required' => false,
            ])

            // Offre active ou non
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ])

            // Ordre d'affichage
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'required' => false,
            ])

            // Début de validité éventuel
            ->add('startsAt', DateTimeType::class, [
                'label' => 'Début',
                'required' => false,
                'widget' => 'single_text',
            ])

            // Fin de validité éventuelle
            ->add('endsAt', DateTimeType::class, [
                'label' => 'Fin',
                'required' => false,
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductOffer::class,
        ]);
    }
}