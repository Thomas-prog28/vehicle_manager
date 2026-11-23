<?php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de véhicule',
                'choices' => [
                    'Voiture' => 'voiture',
                    'Moto' => 'moto',
                ],
                'attr' => [
                    'data-brand-autocomplete-target' => 'typeSelect',
                    'data-action' => 'change->brand-autocomplete#updateBrands',
                ],
            ])
            ->add('brand', TextType::class, [
                'label' => 'Marque',
                'attr' => [
                    'placeholder' => 'Ex. Opel, Peugeot, Honda...',
                    'list' => 'vehicle-brands-list',
                    'autocomplete' => 'off',
                ],
            ])

            ->add('model', TextType::class, ['label' => 'Modèle', 'attr' => ['placeholder' => 'Ex. Zafira Tourer C 1.6 CDTI 136ch, CBF 600...'],])
            ->add('plate', TextType::class, ['label' => 'Immatriculation', 'attr' => ['placeholder' => 'AB-123-CD', 'class' => 'font-mono uppercase',],])
            ->add('firstRegistrationDate', DateType::class, [
                'label' => '1re mise en circulation',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('serviceIntervalKm', IntegerType::class, [
                'label' => 'Intervalle révision (km)',
                'required' => false,
                'attr' => ['placeholder' => 'Auto (ex. 15000 ou 30000)'],
            ])
            ->add('serviceIntervalMonths', IntegerType::class, [
                'label' => 'Intervalle révision (mois)',
                'required' => false,
                'attr' => ['placeholder' => 'Auto (ex. 12 ou 24)'],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Vehicle::class]);
    }
}
