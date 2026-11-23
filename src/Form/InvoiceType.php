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
        $isEdit = $options['is_edit'];

        $fileConstraints = [
            new File(
                maxSize: '10M',
                mimeTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                mimeTypesMessage: 'Formats acceptés : PDF, JPG, PNG ou WebP.'
            ),
        ];

        if (!$isEdit) {
            array_unshift($fileConstraints, new NotBlank(message: 'Veuillez joindre la facture (PDF ou photo).'));
        }

        $builder
            ->add('invoiceFile', FileType::class, [
                'label' => $isEdit ? 'Remplacer le justificatif (optionnel)' : 'Facture (PDF ou photo)',
                'mapped' => false,
                'required' => !$isEdit,
                'constraints' => $fileConstraints,
            ])
            ->add('invoiceDate', DateType::class, [
                'label' => 'Date de la facture',
                'widget' => 'single_text',
                'constraints' => [new NotBlank()],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => Invoice::TYPES,
            ])
            ->add('garage', TextType::class, [
                'label' => 'Garage / Enseigne',
                'required' => false,
                'attr' => ['placeholder' => 'Ex. Norauto, Honda Moto, Oscaro...'],
            ])
            ->add('mileage', IntegerType::class, [
                'label' => 'Kilométrage',
                'required' => false,
                'attr' => ['placeholder' => 'Ex. 65400'],
            ])
            ->add('totalTTC', MoneyType::class, [
                'label' => 'Montant TTC',
                'currency' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Ex. 149.90'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
            'is_edit' => false,
        ]);
    }
}
