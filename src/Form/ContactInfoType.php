<?php
// src/Form/ContactInfoType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;


class ContactInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
            ]);
    }
}