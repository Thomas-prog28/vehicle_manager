<?php

namespace App\Form;

use App\Entity\Invoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('invoiceDate', DateType::class, [
                'label' => 'Date de la facture',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(message: 'La date est obligatoire.'),
                ],
            ])
            ->add('garage', TextType::class, [
                'label' => 'Garage / Prestataire',
                'required' => false,
                'attr' => ['placeholder' => 'Ex. Norauto, Concessionnaire, Pièces auto...'],
            ])
            ->add('mileage', IntegerType::class, [
                'label' => 'Kilométrage au compteur',
                'required' => false,
                'attr' => ['placeholder' => 'Ex. 65400'],
            ])
            ->add('totalTTC', MoneyType::class, [
                'label' => 'Montant TTC',
                'currency' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Ex. 149.90'],
            ])
            ->add('invoiceFile', FileType::class, [
                'label' => 'Document (PDF ou photo)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Veuillez joindre un fichier (PDF ou image).'),
                    new File(
                        maxSize: '10M',
                        mimeTypes: [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Formats acceptés : PDF, JPG, PNG ou WebP (10 Mo max).'
                    ),
                ],
            ])

            ->add('type', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => Invoice::TYPES,
                'placeholder' => 'Choisis une catégorie...'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
        ]);
    }
}
