<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Callback;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('qte', IntegerType::class, [
                'label' => 'Quantité totale',
                'attr' => ['placeholder' => 'Entrer la quantité totale'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une quantité']),
                    new PositiveOrZero(['message' => 'La quantité doit être positive ou zéro']),
                ],
            ])
            ->add('prixUnitaire', MoneyType::class, [
                'currency' => 'TND',
                'label' => 'Prix Unitaire',
                'attr' => ['placeholder' => 'Entrer le prix unitaire'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un prix unitaire']),
                    new PositiveOrZero(['message' => 'Le prix unitaire doit être positif ou zéro']),
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
