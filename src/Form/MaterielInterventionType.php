<?php

namespace App\Form;

use App\Entity\Materiel;
use App\Entity\MaterielIntervention;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class MaterielInterventionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('materiel', EntityType::class, [
                'label'        => 'Matériel',
                'class'        => Materiel::class,
                'choice_label' => fn(Materiel $m) => sprintf('%s (%d en stock)', $m->getNom(), $m->getQuantiteStock()),
                'placeholder'  => '— Choisir un matériel —',
                'constraints'  => [new NotBlank(message: 'Veuillez choisir un matériel.')],
            ])
            ->add('quantite', IntegerType::class, [
                'label'       => 'Quantité',
                'constraints' => [
                    new NotBlank(message: 'La quantité est obligatoire.'),
                    new Positive(message: 'La quantité doit être positive.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MaterielIntervention::class,
        ]);
    }
}