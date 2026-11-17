<?php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
                'choices' => ['Voiture' => 'car', 'Moto' => 'moto'],
            ])
            ->add('brand', TextType::class, ['label' => 'Marque', 'attr' => ['placeholder' => 'Ex. Peugeot, Honda, Renault...'],])
            ->add('model', TextType::class, ['label' => 'Modèle', 'attr' => ['placeholder' => 'Ex. 308 SW, CBF 600...'],])
            ->add('plate', TextType::class, ['label' => 'Immatriculation', 'attr' => ['placeholder' => 'AB-123-CD', 'class' => 'font-mono uppercase',],])
            ->add('firstRegistrationDate', DateType::class, [
                'label' => '1re mise en circulation',
                'widget' => 'single_text',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Vehicle::class]);
    }
}