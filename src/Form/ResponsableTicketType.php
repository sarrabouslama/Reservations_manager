<?php

namespace App\Form;

use App\Entity\ResponsableTicket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Positive;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Repository\ResponsableTicketRepository;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class ResponsableTicketType extends AbstractType
{
    private TicketRepository $ticketRepository;
    private ResponsableTicketRepository $responsableTicketRepository;
    private UserRepository $userRepository;

    public function __construct(TicketRepository $ticketRepository, ResponsableTicketRepository $responsableTicketRepository, UserRepository $userRepository)
    {
        $this->ticketRepository = $ticketRepository;
        $this->responsableTicketRepository = $responsableTicketRepository;
        $this->userRepository = $userRepository;
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

            ->add('qte', IntegerType::class, [
                'label' => 'Quantité totale',
                'attr' => ['placeholder' => 'Entrer la quantité totale pour ce responsable'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une quantité']),
                    new Positive(['message' => 'La quantité doit être supérieure à zéro']),
                    new Callback(function($qte, ExecutionContextInterface $context) {
                        $form = $context->getRoot();
                        $responsable = $this->responsableTicketRepository->findOneBy([
                            'responsable' => $this->userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]),
                            'ticket' => $this->ticketRepository->findOneBy(['localisation' => $form->get('localisation')->getData()]),
                        ]);
                        $qteVente = $responsable ? $responsable->getQteVente() : 0;
                        if ($qteVente > 0 && $qte < $qteVente) {
                            $context->addViolation("La quantité totale ne peut pas être inférieure à la quantité vendue.");
                        }
                    })
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResponsableTicket::class,
            'matricule' => '', // default value
            'localisation' => '',
        ]);
    }
}
