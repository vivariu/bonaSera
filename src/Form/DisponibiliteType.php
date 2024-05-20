<?php

namespace App\Form;

use App\Entity\Disponibilite;
use App\Entity\Logement;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class DisponibiliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_debut', DateType::class, [
                'widget' => 'single_text', // Utiliser un seul champ de texte pour la date
                'required' => true,
            ])
            ->add('date_fin', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('prix', NumberType::class, [
                'required' => true,
            ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Disponibilite::class,
        ]);
    }
}
