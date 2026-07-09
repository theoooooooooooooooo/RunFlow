<?php

namespace App\Form;

use App\Entity\Adresse;
use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Repository\AdresseRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class InterventionClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Utilisateur $client */
        $client = $options['client'];

        $builder
            ->add('description', TextareaType::class, [
                'label'       => 'Description du problème',
                'attr'        => [
                    'rows'        => 5,
                    'placeholder' => 'Décrivez votre problème en détail...',
                ],
                'constraints' => [new NotBlank(message: 'La description est obligatoire.')],
            ])
            ->add('date_souhaitee', DateTimeType::class, [
                'label'       => 'Date et heure souhaitées',
                'widget'      => 'single_text',
                'input'       => 'datetime_immutable',
                'attr'        => [
                    'id'          => 'date-souhaitee-picker',
                    'placeholder' => 'Choisissez une date et une heure',
                    'autocomplete' => 'off',
                ],
                'constraints' => [new NotBlank(message: 'Veuillez indiquer une date souhaitée.')],
            ])

            // Sélection d'une adresse déjà utilisée
            ->add('adresse_existante', EntityType::class, [
                'label'         => 'Adresse d\'intervention',
                'class'         => Adresse::class,
                'choice_label'  => fn(Adresse $a) => (string) $a,
                'placeholder'   => '— Nouvelle adresse —',
                'required'      => false,
                'mapped'        => false,
                'attr'          => ['id' => 'select-adresse-existante'],
                'query_builder' => function (AdresseRepository $repo) use ($client) {
                    return $repo->createQueryBuilder('a')
                        ->innerJoin('a.interventions', 'i')
                        ->andWhere('i.client = :client')
                        ->setParameter('client', $client)
                        ->distinct();
                },
            ])

            // Champs adresse manuelle (utilisés uniquement si "Nouvelle adresse" est choisi)
            ->add('adresse_rue', TextType::class, [
                'label'    => 'Rue',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => '12 rue des Flamboyants'],
            ])
            ->add('adresse_ville', TextType::class, [
                'label'    => 'Ville',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => 'Saint-Denis'],
            ])
            ->add('adresse_code_postal', TextType::class, [
                'label'    => 'Code postal',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => '97400'],
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
        $resolver->setRequired('client');
        $resolver->setAllowedTypes('client', Utilisateur::class);
    }
}