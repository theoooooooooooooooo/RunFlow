<?php

namespace App\Form;

use App\Entity\Adresse;
use App\Entity\Commentaire;
use App\Entity\Intervention;
use App\Entity\Materiel;
use App\Entity\Utilisateur;
use App\Enum\StatutInterventionEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InterventionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_demande', null, [
                'widget' => 'single_text',
            ])
            ->add('date_souhaitee', null, [
                'widget' => 'single_text',
            ])
            ->add('date_planifiee', null, [
                'widget' => 'single_text',
            ])
            ->add('description')
            ->add('statut', ChoiceType::class, [
                'choices' => StatutInterventionEnum::cases(),
                'choice_label' => fn($choice) => $choice->value,
            ])
            ->add('client', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'id',
            ])
            ->add('technicien', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'id',
            ])
            ->add('adresse', EntityType::class, [
                'class' => Adresse::class,
                'choice_label' => 'id',
            ])
            ->add('materiels', EntityType::class, [
                'class' => Materiel::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            ->add('commentaire', EntityType::class, [
                'class' => Commentaire::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Intervention::class,
        ]);
    }
}
