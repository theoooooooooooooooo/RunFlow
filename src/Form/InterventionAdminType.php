<?php

namespace App\Form;

use App\Entity\Intervention;
use App\Enum\StatutInterventionEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                'label'        => 'Statut',
                'choices'      => StatutInterventionEnum::cases(),
                'choice_label' => fn(StatutInterventionEnum $choice) => $choice->label(),
                'choice_value' => fn(?StatutInterventionEnum $choice) => $choice?->value,
                'help'         => 'À utiliser uniquement pour les cas exceptionnels (ex : annulation).',
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