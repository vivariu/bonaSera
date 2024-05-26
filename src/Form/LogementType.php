<?php

namespace App\Form;

use App\Entity\Disponibilite;
use App\Entity\Logement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;


class LogementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Maison' => 'maison',
                    'Appartement' => 'appartement',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ])

            ->add('nb_voyageur', ChoiceType::class, [
                'choices' => array_combine(range(1, 12), range(1, 12)),
                'required' => true,
            ])
            ->add('nb_chambre',  ChoiceType::class, [
                'choices' => array_combine(range(1, 12), range(1, 12)),
                'required' => true,
            ])
            ->add('nb_salle_de_bain', ChoiceType::class, [
                'choices' => array_combine(range(1, 12), range(1, 12)),
                'required' => true,
            ])
            ->add('images', FileType::class, [
                'label' => false,
                'multiple' => true,
                'mapped' => false,
                'required' => false,
            ])
            ->add('disponibilites', CollectionType::class, [
                'entry_type' => DisponibiliteType::class, // Le type de formulaire pour chaque disponibilité
                'allow_add' => true, // Permet d'ajouter de nouvelles disponibilités
                'allow_delete' => true, // Permet de supprimer des disponibilités
                'by_reference' => false, // Utiliser des objets distincts
                'prototype' => true, // Permet d'utiliser un prototype pour JavaScript
                'label' => 'Disponibilités', // Étiquette pour le champ de collection
                'attr' => [
                    'class' => 'collection-holder', // Classe CSS pour identifier la collection
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Logement::class,
        ]);
    }
}
