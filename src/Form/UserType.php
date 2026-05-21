<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Enter full name'
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Enter username',
                    'autocomplete' => 'username',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a username',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Username should be at least {{ limit }} characters',
                        'maxMessage' => 'Username cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Enter email address'
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Role',
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_STAFF',
                    'User' => 'ROLE_USER',
                ],
                'multiple' => false,
                'expanded' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-input',
                ],
            ]);

        // Only add password field when creating new user
        if (!$isEdit) {
            $builder->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Password',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Enter password (min 6 characters)',
                    'autocomplete' => 'new-password'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ]);
        }

        // Transform roles to single selection for display
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $user = $event->getData();
            $form = $event->getForm();
            
            if ($user && $user->getId()) {
                // Use raw roles so we can correctly show ROLE_USER in the edit form
                $rawRoles = $user->getRawRoles();

                if (in_array('ROLE_ADMIN', $rawRoles, true)) {
                    $form->get('roles')->setData('ROLE_ADMIN');
                } elseif (in_array('ROLE_STAFF', $rawRoles, true)) {
                    $form->get('roles')->setData('ROLE_STAFF');
                } elseif (in_array('ROLE_USER', $rawRoles, true)) {
                    $form->get('roles')->setData('ROLE_USER');
                } else {
                    // Fallback
                    $form->get('roles')->setData('ROLE_USER');
                }
            } else {
                // For new users, default to customer role
                $form->get('roles')->setData('ROLE_USER');
            }
        });

        // Transform single role selection back to array on submit
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $user = $event->getData();
            $form = $event->getForm();
            
            $selectedRole = $form->get('roles')->getData();
            // Ensure it's an array for setRoles()
            if (!is_array($selectedRole)) {
                $selectedRole = [$selectedRole];
            }
            $user->setRoles($selectedRole);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}

