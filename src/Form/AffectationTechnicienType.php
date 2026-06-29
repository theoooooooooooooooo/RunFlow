<?php

namespace App\Form;

use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
                        ->orderBy('u.nom', 'ASC'),
            ])
            ->add('date_planifiee', DateType::class, [
                'label'       => 'Date d\'intervention planifiée',
                'widget'      => 'single_text',
                'constraints' => [new NotBlank(message: 'Veuillez choisir une date.')],
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