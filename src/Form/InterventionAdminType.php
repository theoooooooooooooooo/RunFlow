<?php

namespace App\Form;

use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Enum\StatutInterventionEnum;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InterventionAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => ['rows' => 4],
            ])
            ->add('statut', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => StatutInterventionEnum::cases(),
                'choice_label' => fn(StatutInterventionEnum $choice) => $choice->label(),
                'choice_value' => fn(?StatutInterventionEnum $choice) => $choice?->value,
            ])
            ->add('technicien', EntityType::class, [
                'label'        => 'Technicien assigné',
                'class'        => Utilisateur::class,
                'choice_label' => 'nomComplet',
                'required'     => false,
                'placeholder'  => '— Non assigné —',
                'query_builder' => fn(UtilisateurRepository $repo) =>
                    $repo->createQueryBuilder('u')
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_TECHNICIEN%')
                        ->orderBy('u.nom', 'ASC'),
            ])
            ->add('date_souhaitee', DateType::class, [
                'label'    => 'Date souhaitée par le client',
                'widget'   => 'single_text',
                'required' => false,
            ])
            ->add('date_planifiee', DateType::class, [
                'label'    => 'Date planifiée',
                'widget'   => 'single_text',
                'required' => false,
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