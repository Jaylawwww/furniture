<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ContactMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Customer Support' => 'support',
                    'Visit Us' => 'visit',
                    'Business Inquiries' => 'business',
                ],
                'placeholder' => 'Select a category',
                'required' => true,
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your name']),
                    new Length(['max' => 120]),
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your email']),
                    new Email(['message' => 'Please enter a valid email']),
                    new Length(['max' => 180]),
                ],
            ])
            ->add('subject', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a subject']),
                    new Length(['max' => 150]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your message']),
                    new Length(['min' => 10, 'minMessage' => 'Message should be at least {{ limit }} characters']),
                ],
            ])
            // Honeypot anti-spam field (should remain empty)
            ->add('website', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'contact_message',
        ]);
    }
}

