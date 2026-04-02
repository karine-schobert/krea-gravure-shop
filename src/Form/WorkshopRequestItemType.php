<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\WorkshopRequestItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkshopRequestItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /*
            |------------------------------------------------------------------
            | Sélection catalogue optionnelle
            |------------------------------------------------------------------
            |
            | En V1, on laisse le client libre :
            | - de choisir une catégorie
            | - de choisir un produit
            | - ou simplement de décrire son idée avec un libellé libre
            |
            | L'entité WorkshopRequestItem impose seulement qu'au moins un de
            | ces trois éléments existe : category, product ou customLabel.
            |
            */

            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie…',
                'required' => false,
            ])

            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'title',
                'label' => 'Produit',
                'placeholder' => 'Choisir un produit…',
                'required' => false,
            ])

            /*
            |------------------------------------------------------------------
            | Libellé libre
            |------------------------------------------------------------------
            |
            | Ce champ est très important pour la V1.
            | Il permet au client d’exprimer son besoin même s’il ne trouve pas
            | exactement son produit dans le catalogue.
            |
            */

            ->add('customLabel', TextType::class, [
                'label' => 'Idée produit / libellé libre',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : porte-clé pour maîtresse / plaque entreprise / cadeau invité',
                ],
            ])

            /*
            |------------------------------------------------------------------
            | Quantité
            |------------------------------------------------------------------
            |
            | Optionnelle en V1, mais utile pour devis / demande pro /
            | événement / précommande.
            |
            */

            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité souhaitée',
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'placeholder' => 'Ex : 10',
                ],
            ])

            /*
            |------------------------------------------------------------------
            | Personnalisation et détails de fabrication
            |------------------------------------------------------------------
            |
            | Tous ces champs restent facultatifs en V1.
            | Ils servent à récupérer un maximum de contexte sans bloquer
            | inutilement la soumission.
            |
            */

            ->add('personalizationText', TextareaType::class, [
                'label' => 'Texte / personnalisation souhaitée',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Ex : prénom, date, phrase, logo, message à graver…',
                ],
            ])

            ->add('materialNotes', TextType::class, [
                'label' => 'Matière souhaitée',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : bois clair, contreplaqué, MDF, à définir…',
                ],
            ])

            ->add('formatNotes', TextType::class, [
                'label' => 'Format / forme',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : rond, rectangle, ovale, marque-page…',
                ],
            ])

            ->add('colorNotes', TextType::class, [
                'label' => 'Couleur / finition',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : naturel, peint, verni, à discuter…',
                ],
            ])

            ->add('dimensionsNotes', TextType::class, [
                'label' => 'Dimensions approximatives',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : 10 cm, format A6, petit modèle…',
                ],
            ])

            ->add('lineMessage', TextareaType::class, [
                'label' => 'Précisions supplémentaires pour cette ligne',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Ajoute ici toute précision utile sur ce besoin produit.',
                ],
            ]);

        /*
        |----------------------------------------------------------------------
        | Champs volontairement exclus du formulaire public
        |----------------------------------------------------------------------
        |
        | On n’expose pas ici :
        | - workshopRequest : géré automatiquement par la relation parente
        | - position : pourra être géré plus tard
        | - createdAt / updatedAt : purement techniques
        |
        */
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkshopRequestItem::class,
        ]);
    }
}