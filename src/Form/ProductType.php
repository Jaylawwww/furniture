<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'form-input']
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-input', 'rows' => 5]
            ])
            ->add('price', TextType::class, [
                'attr' => [
                    'class' => 'form-input',
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0.01'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a price',
                    ]),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Price must be greater than 0',
                    ]),
                ],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock',
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'min' => 0,
                    'placeholder' => 'Enter stock quantity'
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Product Image',
                'mapped' => false,
                'required' => false,
                // Removed File constraint to avoid MIME type validation (requires fileinfo extension)
                // File validation is done in the controller based on extension
                'attr' => [
                    'class' => 'form-input file-input',
                    'accept' => 'image/*'
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a Category',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
