<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\WorkshopRequestItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
            | En V1, le client peut :
            | - choisir une catégorie
            | - choisir un produit
            | - ou simplement décrire son besoin via un libellé libre
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
            | Permet au client de décrire une idée qui n'existe pas encore
            | clairement dans le catalogue.
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
            | Personnalisation
            |------------------------------------------------------------------
            */

            ->add('personalizationText', TextareaType::class, [
                'label' => 'Texte / personnalisation souhaitée',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Ex : prénom, date, phrase, logo, message à graver…',
                ],
            ])

            /*
            |------------------------------------------------------------------
            | Matière souhaitée
            |------------------------------------------------------------------
            |
            | On passe ici en liste prédéfinie pour normaliser les demandes.
            |
            */

            ->add('materialNotes', ChoiceType::class, [
                'label' => 'Matière souhaitée',
                'required' => false,
                'placeholder' => 'Choisir une matière…',
                'choices' => [
                    'Bois naturel' => 'bois_naturel',
                    'À définir ensemble' => 'a_definir',
                ],
            ])

            /*
            |------------------------------------------------------------------
            | Format / forme
            |------------------------------------------------------------------
            |
            | Liste prédéfinie selon les formats réellement proposés
            | ou fréquemment demandés dans l’atelier.
            |
            */

            ->add('formatNotes', ChoiceType::class, [
                'label' => 'Format / forme',
                'required' => false,
                'placeholder' => 'Choisir un format…',
                'choices' => [
                    'Porte-clés rond' => 'porte_cle_rond',
                    'Porte-clés rectangulaire' => 'porte_cle_rectangulaire',
                    'Porte-clés ovale' => 'porte_cle_ovale',
                    'Marque-page' => 'marque_page',
                    'Plaque prénom' => 'plaque_prenom',
                    'Plaque entreprise' => 'plaque_entreprise',
                    'Dessous de verre rond' => 'dessous_verre_rond',
                    'Dessous de verre carré' => 'dessous_verre_carre',
                    'Boule de Noël' => 'boule_noel',
                    'Bijoux' => 'bijoux',
                    'Autre format' => 'autre',
                ],
            ])

            /*
            |------------------------------------------------------------------
            | Couleur / finition
            |------------------------------------------------------------------
            |
            | Liste fermée basée sur les possibilités réelles de l’atelier.
            |
            */

            ->add('colorNotes', ChoiceType::class, [
                'label' => 'Couleur / finition',
                'required' => false,
                'placeholder' => 'Choisir une finition…',
                'choices' => [
                    'Naturel' => 'naturel',
                    'Vernis transparent' => 'vernis_transparent',
                    'Vernis brillant' => 'vernis_brillant',
                    'Jaune' => 'jaune',
                    'Bleu' => 'bleu',
                    'Rouge' => 'rouge',
                    'Vert' => 'vert',
                    'Noir' => 'noir',
                    'À définir ensemble' => 'a_definir',
                ],
            ])

            /*
            |------------------------------------------------------------------
            | Dimensions libres
            |------------------------------------------------------------------
            |
            | On laisse ce champ libre car il peut varier fortement selon
            | la demande client.
            |
            */

            ->add('dimensionsNotes', TextType::class, [
                'label' => 'Dimensions approximatives',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : 10 cm, format A6, petit modèle…',
                ],
            ])

            /*
            |------------------------------------------------------------------
            | Précisions complémentaires
            |------------------------------------------------------------------
            |
            | Sert à récupérer tout ce qui ne rentre pas dans les champs
            | standardisés ci-dessus.
            |
            */

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