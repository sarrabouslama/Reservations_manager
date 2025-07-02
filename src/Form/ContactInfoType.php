<?php
// src/Form/ContactInfoType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\Length as length;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Regex;



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
                    new EmailConstraint(['message' => 'Veuillez entrer une adresse e-mail valide']),
                ],
                'empty_data' => '', 
            ])
            ->add('tel', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['placeholder' => 'Entrer numéro de téléphone'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un numéro de téléphone']),
                    new Regex([
                        'pattern' => '/^\d{8}$/',
                        'message' => 'Le numéro de téléphone doit comporter exactement 8 chiffres',
                    ]),
                ],
            ]);
    }
}