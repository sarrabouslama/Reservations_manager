<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Entrer nom'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuiller entrer un nom']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'Entrer email address'],
                'required' => false,
                'constraints' => [
                    new EmailConstraint(['message' => 'Adresse email invalide']),
                ],
            ])
            ->add('tel', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['placeholder' => 'Entrer numéro de téléphone'],
                'required' => false,
                'constraints' => [
                    new length([
                        'min' => 8,
                        'max' => 8,
                        'minMessage' => 'Le numéro de téléphone doit comporter 8 chiffres',
                        'maxMessage' => 'Le numéro de téléphone doit comporter 8 chiffres',
                    ]),
                ],
            ])
            ->add('sit', ChoiceType::class, [
                'label' => 'Situation',
                'choices' => [
                    'Marié(e)' => 'M',
                    'Célibataire' => 'C',
                    'Divorcé(e)' => 'D',
                    'Veuf(ve)' => 'V',
                ],
                'placeholder' => 'Sélectionner une situation',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une situation']),
                ],
                ])
            ->add('Nb_enfants', IntegerType::class, [
                'label' => 'Nombre d\'enfants',
                'attr' => ['placeholder' => 'Entrer le nombre d\'enfants'],
                'required' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le nombre d\'enfants']),
                    new PositiveOrZero([
                        'message' => 'Le nombre d\'enfants doit être positif ou zéro',
                    ]),
                ],
            ])
            ->add('emploi', TextType::class, [
                'label' => 'Emploi',
                'attr' => ['placeholder' => 'Entrer emploi'],
                'required' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un emploi']),
                ],
            ])
            ->add('Matricule_cnss', TextType::class, [
                'label' => 'Matricule CNSS',
                'attr' => ['placeholder' => 'Entrer matricule CNSS'],
                'required' => false,
            ])
            ->add('direction', TextType::class, [
                'label' => 'Direction',
                'attr' => ['placeholder' => 'Entrer direction'],
                'required' => false,
                ])
            ->add('imageFile', FileType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG)',
                    ])
                ],
            ]);
        if ($options['is_admin']){
            $builder
                ->add('matricule', TextType::class, [
                    'label' => 'Matricule',
                    'attr' => ['placeholder' => 'Entrer matricule'],
                    'constraints' => [
                        new NotBlank(['message' => 'Veuiller entrer une matricule']),
                    ],
                ])
                ->add('cin', TextType::class, [
                    'label' => 'CIN',
                    'attr' => ['placeholder' => 'Entrer CIN'],
                    'constraints' => [
                        new NotBlank(['message' => 'Veuiller entrer un CIN']),
                    ],
                ])
                ->add('lastYear', CheckboxType::class, [
                    'label' => "L'adhérent est-il sélectionné la dernière année ?",
                    'required' => false,
                ])
                ->add('actif', CheckboxType::class, [
                    'label' => 'Actif',
                    'required' => false,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_admin' => false, 
        ]);
    }
}

