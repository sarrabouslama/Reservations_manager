<?php

namespace App\Form;

use App\Entity\Home;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use App\Validator\ZeroToOne;
use Symfony\Component\Validator\Constraints\PositiveOrZero;


class HomeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            
            ->add('region', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une region',
                    ]),
                ],
            ])
            ->add('residence', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une residence',
                    ]),
                ],
            ])
            ->add('nombreChambres', IntegerType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le nombre de chambres',
                    ]),
                    new Positive([
                        'message' => 'Le nombre de chambres doit être positif',
                    ]),
                ],
            ])
            ->add('distancePlage', NumberType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer la distance de la plage',
                    ]),
                    new PositiveOrZero([
                        'message' => 'La distance doit être positive',
                    ]),
                ],
            ])
            ->add('prix', MoneyType::class, [
                'currency' => 'TND',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un prix',
                    ]),
                    new PositiveOrZero([
                        'message' => 'Le prix doit être positif ou zéro',
                    ]),
                    
                ],
            ])
            ->add('maxUsers', IntegerType::class, [
                'label' => 'Nombre de maisons',
                'required' => true,
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('imageFiles', FileType::class, [
                'label' => 'Images de la maison',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => 'image/* video/*',
                    'data-bs-toggle' => 'tooltip',
                    'title' => 'Vous pouvez sélectionner plusieurs images ou vidéos (JPG, JPEG, PNG, MP4)',
                ],
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/png',
                                    'image/jpg',
                                    'video/mp4',
                                    'application/octet-stream', 
                                ],
                                'mimeTypesMessage' => 'Veuillez télécharger une image ou vidéo valide (JPG, JPEG, PNG, MP4)',
                            ])
                        ]
                    ])
                ],
            ])
            ->add('mapsUrl', TextType::class, [
                'label' => 'Lien Google Maps',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Collez ici le lien Google Maps de la localisation de la maison',
                ],
            ])
            ->add('telProp', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('nomProp', TextType::class, [
                'label' => 'Nom du propriétaire',
                'required' => false,
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Home::class,
        ]);
    }
}