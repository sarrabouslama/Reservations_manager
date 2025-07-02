<?php

namespace App\Form;

use App\Entity\HomePeriod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class HomePeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateDebut', DateType::class, [
            'widget' => 'single_text',
            'label' => 'Date de début',
            'required' => true,
            'format' => 'dd/MM/yyyy',
            'html5' => false,
            'attr' => [
                'id' => 'dateDebut',
                'class' => 'form-control datepicker',
                'autocomplete' => 'off',
            ],
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
                'required' => true,
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'attr' => [
                    'id' => 'dateDebut',
                    'class' => 'form-control datepicker',
                    'autocomplete' => 'off',
                ],
                'constraints' => [
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) {
                            $dateDebut = $context->getRoot()->get('dateDebut')->getData();
                            if ($dateDebut && $value <= $dateDebut) {
                                $context->addViolation("La date de fin doit être postérieure à dateDebut .", [
                                    'dateDebut' => $dateDebut->format('d/m/Y'),
                                ]);
                            }
                        },
                    ]),
                ],
            ]);

            if ($options['one_period']){
                $builder->add('maxUsers', IntegerType::class, [
                    'label' => 'Nombre de maisons',
                    'required' => true,
                    'attr' => [
                        'min' => 1,
                    ],
                ]);
            }
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HomePeriod::class,
            'one_period' => true, 
        ]);
    }
} 