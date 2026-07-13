<?php

namespace App\Form;

use App\Entity\Materiel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class MaterielType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label'       => 'Nom du matériel',
                'attr'        => ['placeholder' => 'Ex: Joint torique 20mm'],
                'constraints' => [new NotBlank(message: 'Le nom est obligatoire.')],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['rows' => 3, 'placeholder' => 'Détails, référence, usage...'],
            ])
            ->add('quantite_stock', IntegerType::class, [
                'label'       => 'Quantité en stock',
                'constraints' => [
                    new NotBlank(message: 'La quantité est obligatoire.'),
                    new PositiveOrZero(message: 'La quantité ne peut pas être négative.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Materiel::class,
        ]);
    }
}