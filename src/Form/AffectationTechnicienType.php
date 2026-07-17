<?php

namespace App\Form;

use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AffectationTechnicienType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('technicien', EntityType::class, [
                'label'        => 'Technicien',
                'class'        => Utilisateur::class,
                'choice_label' => 'nomComplet',
                'placeholder'  => '— Choisir un technicien —',
                'constraints'  => [new NotBlank(message: 'Veuillez choisir un technicien.')],
                'query_builder' => fn(UtilisateurRepository $repo) =>
                    $repo->createQueryBuilder('u')
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_TECHNICIEN%')
                        ->andWhere('u.actif = true')
                        ->orderBy('u.nom', 'ASC'),
            ])
            ->add('duree_estimee', ChoiceType::class, [
                'label'   => 'Durée estimée',
                'choices' => [
                    '30 minutes' => 30,
                    '1 heure'    => 60,
                    '1h30'       => 90,
                    '2 heures'   => 120,
                    '3 heures'   => 180,
                    '4 heures'   => 240,
                    'Journée complète (8h)' => 480,
                ],
                'data'        => 120,
                'constraints' => [new NotBlank(message: 'Veuillez estimer une durée.')],
            ])
            ->add('date_planifiee', DateTimeType::class, [
                'label'       => 'Date planifiée',
                'widget'      => 'single_text',
                'input'       => 'datetime_immutable',
                'constraints' => [new NotBlank(message: 'Veuillez sélectionner un créneau sur le calendrier.')],
            ])
            ->add('materielInterventions', CollectionType::class, [
                'label'         => false,
                'entry_type'    => MaterielInterventionType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
                'required'      => false,
                'prototype'     => true,
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