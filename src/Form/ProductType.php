<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image as ImageConstraint;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('imageFiles', FileType::class, [
                'label' => 'Obrázky (lze vybrat více souborů)',
                'multiple' => true, // Povolí výběr více souborů
                'mapped' => false, // DŮLEŽITÉ: Říká Symfony, aby se nesnažilo toto pole ukládat do databáze
                'required' => false, // Pole není povinné
                'constraints' => [
                    new All([ // Validace se aplikuje na každý soubor zvlášť
                        new ImageConstraint([
                            'maxSize' => '5M',
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ],
                            'mimeTypesMessage' => 'Prosím nahrajte platný obrázek (JPEG, PNG, WebP).',
                        ])
                    ])
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Uložit produkt'
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
