<?php

namespace App\Form;

use App\Entity\UserTicket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Positive;
use App\Repository\TicketRepository;



class UserTicketType extends AbstractType
{
    private TicketRepository $ticketRepository;

    public function __construct(TicketRepository $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $localisations = $this->ticketRepository->findAll();
        $choices = [];
        foreach ($localisations as $ticket) {
            $choices[$ticket->getLocalisation()] = $ticket->getLocalisation();
        }
        $builder
            ->add('matricule',TextType::class, [
                'label' => 'Matricule',
                'attr' => ['placeholder' => 'Entrer matricule'],
                'mapped' => false,
                'data' => $options['matricule'] ?? '',
                'constraints' => [
                    new NotBlank(['message' => 'Veuiller entrer une matricule']),
                ],
            ])

            ->add('localisation',ChoiceType::class, [
                'label' => 'Localisation',
                'mapped' => false,
                'data' => $options['localisation'] ?? '',
                'choices' => $choices,
                'placeholder' => 'Sélectionner une Localisation',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une Localisation']),
                ],
            ])
            ->add('nombre', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => ['placeholder' => 'Entrer la quantité voulue'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une quantité']),
                    new Positive(['message' => 'La quantité doit être supérieure à zéro']),
                ],
            ])
            
            ->add('avance', MoneyType::class, [
                'currency' => 'TND',
                'label' => 'Avance',
                'attr' => ['placeholder' => 'Entrer le montant de l\'avance'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un montant d\'avance']),
                    new PositiveOrZero(['message' => 'L\'avance doit être positive ou zéro']),
                ],
            ])

            ->add('nbMois', IntegerType::class, [
                'label' => 'Nombre de Mois',
                'attr' => ['placeholder' => 'Entrer le nombre de mois'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le nombre de mois']),
                    new PositiveOrZero(['message' => 'Le nombre de mois doit être positif ou zéro']),
                ],
            ])
            ->add('modeEcheance',ChoiceType::class, [
                'label' => 'Mode d\'Échéance',
                'choices' => [
                    '1- Paie' => '1',
                    '2- Prime de rendement' => '2',
                    '3- 13eme' => '3',
                    '4- paie et prime' => '4',
                    '5- paie et 13eme' => '5',
                    '6- Tous les salaires' => '6',
                    '7- Tous les salaires(2fois p & 13)' => '7',
                    '8- Salaire et double 13eme' => '8',
                    '9- Prime et 13eme' => '9',
                    '10- Prime de rendement complete' => '10',
                ],
                'placeholder' => 'Sélectionner un mode d\'échéance',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un mode d\'échéance']),
                ],
            ])
            ->add('codeOpposition', ChoiceType::class, [
                'label' => 'Code d\'Opposition',
                'choices' => [
                    '1000 Cotisation Amicale Siège' => '1000',
                    '1001 Orange' => '1001',
                    '1011 Ooredoo' => '1011',
                    '1021 Conventions Achats' => '1021',
                    '1031 Tickets Amicale' => '1031',
                    '1041 Hotels été' => '1041',
                    '1051 Hotels Printemps' => '1051',
                    '1061 Excursions à l\'étranger' => '1061',
                    '1071 Assurances (amicale)' => '1071',
                    '1081 Actions sociales (Amicale)' => '1081',
                    '1091 Appurement' => '1091',
                ],
                'placeholder' => 'Sélectionner un code d\'opposition',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un code d\'opposition']),
                ],
            ])
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
                'constraints' => [
                    new Callback(function($dateDebut, ExecutionContextInterface $context) {
                        $form = $context->getRoot();
                        $date = (new \DateTime())->setTime(0, 0, 0);
                        if ($dateDebut < $date) {
                            $context->addViolation("La date de début doit être postérieure à la date actuelle.");
                        }
                    })
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserTicket::class,
            'matricule' => '', // default value
            'localisation' => '',
        ]);
    }
}
