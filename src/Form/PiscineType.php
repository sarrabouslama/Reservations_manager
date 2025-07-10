<?php

namespace App\Form;

use App\Entity\Piscine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class PiscineType extends AbstractType
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
            ->add('hotel', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un hotel',
                    ]),
                ],
            ])
            ->add('prixInitial', MoneyType::class, [
                'currency' => 'TND',
                'label' => 'Prix Initial',
                'attr' => ['placeholder' => 'Entrer le prix initial'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un prix initial']),
                    new Positive(['message' => 'Le prix initial doit être supérieur à zéro']),
                ],
            ])
            ->add('consommation', MoneyType::class, [
                'currency' => 'TND',
                'label' => 'Consommation',
                'attr' => ['placeholder' => 'Entrer la consommation'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une consommation']),
                    new PositiveOrZero(['message' => 'La consommation doit être supérieure ou égale à zéro']),
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) {
                            $prixInitial = $context->getRoot()->get('prixInitial')->getData();
                            if ($prixInitial && $value > $prixInitial) {
                                $context->addViolation("La consommation ne peut pas être supérieure au prix initial.");
                            }
                        },
                    ]),
                ],
            ])
            ->add('amicale', MoneyType::class, [
                'currency' => 'TND',
                'label' => 'Amicale',
                'attr' => ['placeholder' => 'Entrer la réduction amicale'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une réduction amicale']),
                    new Positive(['message' => 'La réduction doit être supérieure à zéro']),
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) {
                            $prixInitial = $context->getRoot()->get('prixInitial')->getData();
                            if ($prixInitial && $value > $prixInitial) {
                                $context->addViolation("La réduction ne peut pas être supérieure au prix initial.");
                            }
                        },
                    ]),
                ],
            ])
            ->add('avance', MoneyType::class, [
                'currency' => 'TND',
                'label' => 'Avance',
                'attr' => ['placeholder' => 'Entrer l\'avance par défaut'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une avance par défaut']),
                    new Positive(['message' => 'L\'avance doit être supérieure à zéro']),
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) {
                            $prixInitial = $context->getRoot()->get('prixInitial')->getData();
                            $amicale = $context->getRoot()->get('amicale')->getData();
                            $prixFinal = $prixInitial - $amicale;
                            if ($prixFinal && $value > $prixFinal) {
                                $context->addViolation("L'avance ne peut pas être supérieure au prix final.");
                            }
                        },
                    ]),
                ],
            ])
            ->add('dateLimite', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
                'required' => false,
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'attr' => [
                    'id' => 'dateLimite',
                    'class' => 'form-control datepicker',
                    'autocomplete' => 'off',
                ],
                'constraints' => [
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) {
                            $date = (new \DateTime())->setTime(0, 0, 0);
                            if ($value && $value < $date) {
                                $context->addViolation("La date limite doit être postérieure à la date actuelle.");
                            }
                        },
                    ]),
                ],
            ])
            ->add('entree', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date d\'entrée',
                'required' => false,
                'format' => 'HH:mm',
                'html5' => false,
                'attr' => [
                    'id' => 'entree',
                    'class' => 'form-control datepicker',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('sortie', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de sortie',
                'required' => false,
                'format' => 'HH:mm',
                'html5' => false,
                'attr' => [
                    'id' => 'sortie',
                    'class' => 'form-control datepicker',
                    'autocomplete' => 'off',
                ],
                'constraints' => [
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) {
                            $entree = $context->getRoot()->get('entree')->getData();
                            if ($entree && $value <= $entree) {
                                $context->addViolation("L'heure de sortie doit être après l'heure d'entrée.");
                            }
                        },
                    ]),
                ],
            ])
            ->add('nbEnfants', IntegerType::class, [
                'label' => 'Nombre d\'enfants',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Entrer le nombre d\'enfants',
                ],
                'constraints' => [
                    new PositiveOrZero(['message' => 'Le nombre d\'enfants doit être positif ou zéro']),
                ],

            ])
            ->add('nbAdultes', IntegerType::class, [
                'label' => 'Nombre d\'adultes',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Entrer le nombre d\'adultes',
                ],
                'constraints' => [
                    new PositiveOrZero(['message' => 'Le nombre d\'adultes doit être positif ou zéro']),
                ],
                ])
            ->add('nbPersonnes', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Entrer le nombre de personnes',
                ],
                'constraints' => [
                    new PositiveOrZero(['message' => 'Le nombre de personnes doit être positif ou zéro']),
                    new NotBlank(['message' => 'Le nombre de personnes est requis']),
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) {
                            $nbEnfants = $context->getRoot()->get('nbEnfants')->getData();
                            $nbAdultes = $context->getRoot()->get('nbAdultes')->getData();
                            if (($nbAdultes > 0 || $nbEnfants > 0) && $value !== ($nbEnfants + $nbAdultes)) {
                                $context->addViolation("Le nombre de personnes doit être égal à la somme des enfants et des adultes.");
                            }
                        },
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Entrer une description',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Piscine::class,
        ]);
    }
}
