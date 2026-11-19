<?php

namespace App\Form;

use App\Entity\Insurance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class InsuranceCoverageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('insuranceFile', FileType::class, [
                'label' => 'Attestation ou échéancier (PDF / photo)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Veuillez joindre votre document d’assurance.'),
                    new File(
                        maxSize: '12M',
                        mimeTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Formats acceptés : PDF, JPG, PNG ou WebP.'
                    ),
                ],
            ])
            ->add('insurer', TextType::class, [
                'label' => 'Compagnie d’assurance',
                'attr' => ['placeholder' => 'Ex. MAIF, Allianz, AXA...'],
                'constraints' => [new NotBlank()],
            ])
            ->add('contractNumber', TextType::class, [
                'label' => 'Numéro de contrat',
                'attr' => ['placeholder' => 'Ex. 12345678A', 'class' => 'font-mono'],
                'constraints' => [new NotBlank()],
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'constraints' => [new NotBlank()],
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date d’échéance',
                'widget' => 'single_text',
                'constraints' => [new NotBlank()],
            ])
            ->add('premium', MoneyType::class, [
                'label' => 'Cotisation pour ce véhicule',
                'currency' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'Ex. 450.00'],
                'constraints' => [new NotBlank(message: 'Indique le montant de la cotisation.')],
            ])
            ->add('totalAmount', HiddenType::class, [
                'required' => false,
                'attr' => ['data-scan-field' => 'totalAmount'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Insurance::class,
        ]);
    }
}
