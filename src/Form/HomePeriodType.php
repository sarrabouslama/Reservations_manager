<?php

namespace App\Form;

use App\Entity\HomePeriod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ]);
            
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HomePeriod::class,
        ]);
    }
} 