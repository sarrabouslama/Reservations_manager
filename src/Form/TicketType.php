<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('localisation',TextType::class, [
                'label' => 'localisation',
                'attr' => ['placeholder' => 'Entrer localisation'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuiller entrer une localisation']),
                ],
            ])
            ->add('qte', IntegerType::class, [
                'label' => 'Quantité totale',
                'attr' => ['placeholder' => 'Entrer la quantité totale'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une quantité']),
                    new Positive(['message' => 'La quantité doit être supérieure à zéro']),
                ],
            ])
            ->add('prixUnitaire', MoneyType::class, [
                'currency' => 'TND',
                'label' => 'Prix Unitaire',
                'attr' => ['placeholder' => 'Entrer le prix unitaire'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un prix unitaire']),
                    new Positive(['message' => 'Le prix unitaire doit être supérieur à zéro']),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                            'image/heic',
                            'image/heif',
                            'image/jfif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}
