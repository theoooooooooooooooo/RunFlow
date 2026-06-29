<?php

namespace App\Form;

use App\Entity\Adresse;
use App\Entity\Intervention;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Future;

class InterventionClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label'       => 'Description du problème',
                'attr'        => [
                    'rows'        => 5,
                    'placeholder' => 'Décrivez votre problème en détail...',
                ],
                'constraints' => [new NotBlank(message: 'La description est obligatoire.')],
            ])
            ->add('date_souhaitee', DateType::class, [
                'label'   => 'Date d\'intervention souhaitée',
                'widget'  => 'single_text',
                'constraints' => [new NotBlank(message: 'Veuillez indiquer une date souhaitée.')],
            ])
            // Champs adresse embarqués directement
            ->add('adresse_rue', TextType::class, [
                'label'       => 'Rue',
                'mapped'      => false,
                'attr'        => ['placeholder' => '12 rue des Flamboyants'],
                'constraints' => [new NotBlank(message: 'La rue est obligatoire.')],
            ])
            ->add('adresse_ville', TextType::class, [
                'label'       => 'Ville',
                'mapped'      => false,
                'attr'        => ['placeholder' => 'Saint-Denis'],
                'constraints' => [new NotBlank(message: 'La ville est obligatoire.')],
            ])
            ->add('adresse_code_postal', TextType::class, [
                'label'       => 'Code postal',
                'mapped'      => false,
                'attr'        => ['placeholder' => '97400'],
                'constraints' => [new NotBlank(message: 'Le code postal est obligatoire.')],
            ])
            ->add('adresse_complement', TextType::class, [
                'label'    => 'Complément d\'adresse',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => 'Appartement, étage... (facultatif)'],
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