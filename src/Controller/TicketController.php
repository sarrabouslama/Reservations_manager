<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TicketRepository;
use App\Repository\ResponsableTicketRepository;
use App\Repository\UserTicketRepository;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Entity\UserTicket;
use App\Entity\ResponsableTicket;
use App\Entity\Ticket;
use App\Form\TicketType;
use App\Form\ResponsableTicketType;
use App\Form\UserTicketType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;




class TicketController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/tickets', name: 'admin_tickets')]
    public function index(TicketRepository $ticketRepository, EntityManagerInterface $entityManager): Response
    {
        $tickets = $ticketRepository->findAll();
        if (!$tickets) {
            $ticket = new Ticket();
            $ticket->setQte(0);
            $ticket->setPrixUnitaire(0);
            $ticket->setTotalVente(0);
            $ticket->setTotalAvance(0);
            $ticket->setQteVente(0);
            $entityManager->persist($ticket);
            $entityManager->flush();
            $tickets = [$ticket];
        }
        return $this->render('ticket/tickets.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/tickets/edit/{id}', name: 'admin_edit_ticket')]
    public function editTicket(Request $request, int $id, TicketRepository $ticketRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have acces to this page.');
        }
        $ticket = $ticketRepository->find($id);
        if (!$ticket) {
            $ticket = new Ticket();
            $ticket->setQte(0);
            $ticket->setPrixUnitaire(0);
            $ticket->setTotalVente(0);
            $ticket->setTotalAvance(0);
            $ticket->setQteVente(0);
            $entityManager->persist($ticket);
            $entityManager->flush();
        }

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ticket->setQte($form->get('qte')->getData());
            $ticket->setPrixUnitaire($form->get('prixUnitaire')->getData());
            $entityManager->persist($ticket);
            $entityManager->flush();
            $this->addFlash('success', 'Le ticket a été mis à jour avec succès.');
            return $this->redirectToRoute('admin_tickets');
        }

        return $this->render('ticket/edit_ticket.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/tickets/responsable', name: 'admin_tickets_responsable')]
    public function responsableTickets(Request $request, ResponsableTicketRepository $responsableTicketRepository, UserTicketRepository $userTicketRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10;
        $matricule = $request->query->get('matricule');

        $queryBuilder = $responsableTicketRepository->createQueryBuilder('t')
            ->leftJoin('t.responsable', 'u')
            ->addSelect('u');


        if ($matricule) {
            $queryBuilder->andWhere('u.matricule = :matricule')
                ->setParameter('matricule', $matricule);
        }

        $query =  $queryBuilder->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery();

        $responsableTickets = new Paginator($query, true);
        
        return $this->render('ticket/responsable_tickets.html.twig', [
            'responsableTickets' => $responsableTickets,
            'currentPage' => $page,
            'totalPages' => ceil(count($responsableTickets) / $pageSize),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ticket/responsable/{id}', name:'admin_edit_responsable', requirements: ['id' => '\\d+'])]
    public function editResponsable(Request $request, int $id, UserRepository $userRepository, TicketRepository $ticketRepository, ResponsableTicketRepository $ticketResponsableRepository, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have acces to this page.');
        }
        $ticketResponsable = $ticketResponsableRepository->find($id);
        if (!$ticketResponsable) {
            $this->addFlash('danger', 'Le responsable n\'est pas trouvé.');
            return $this->redirectToRoute('admin_tickets_responsable');
        }
        $qte = $ticketResponsable->getQte();
        $ticketResponsables = $ticketResponsableRepository->findAll();

        $form = $this->createForm(ResponsableTicketType::class, $ticketResponsable, [
            'matricule' => $ticketResponsable->getResponsable() ? $ticketResponsable->getResponsable()->getMatricule() : '',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('matricule')->getData() !== $ticketResponsable->getResponsable()->getMatricule()){
                $user = $userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]);
                if (!$user){
                    $this->addFlash('danger', 'Aucun utilisateur trouvé pour ce matricule.');
                    return $this->redirectToRoute('admin_edit_responsable', [
                        'id'=>$id,
                    ]);
                }
                if (in_array($user->getResponsableTicket(), $ticketResponsables)){
                    $this->addFlash('danger', 'Utilisateur déjà ajouté.');
                    return $this->redirectToRoute('admin_edit_responsable', [
                        'id'=>$id,
                    ]);
                }
            }


            if ($form->get('qte')->getData() !== $qte){
                $sum=0;

                $tickets = $ticketRepository->findAll();
                foreach ($tickets as $ticket){
                    $sum+=$ticket->getQte();
                }

                foreach ($ticketResponsables as $responsable){
                    if ($responsable->getResponsable()->getId() !== $ticketResponsable->getResponsable()->getId()){
                        $sum-=$responsable->getQte();
                    }
                }

                if ($sum-$form->get('qte')->getData() < 0){
                    $this->addFlash('danger', "La quantité est suppérieure au nombre de tickets disponibles.");
                    return $this->redirectToRoute('admin_edit_responsable', [
                        'id'=>$id,
                    ]);
                }
            }

            $ticketResponsable->setResponsable($userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]));
            $ticketResponsable->setQte($form->get('qte')->getData());
            $entityManager->persist($ticketResponsable);
            $entityManager->flush();
            $this->addFlash('success', 'Le Resoonsable a été mis à jour avec succès.');
            return $this->redirectToRoute('admin_tickets_responsable');
        }

        return $this->render('ticket/edit_ticketResponsable.html.twig', [
            'ticketResponsable' => $ticketResponsable,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ticket/responsable/add', name:'admin_add_responsable')]
    public function addResponsable(Request $request, UserRepository $userRepository, TicketRepository $ticketRepository, ResponsableTicketRepository $ticketResponsableRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have acces to this page.');
        }
        $ticketResponsables = $ticketResponsableRepository->findAll();
        
        $ticketResponsable = new ResponsableTicket();

        $form = $this->createForm(ResponsableTicketType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]);
            if (!$user){
                $this->addFlash('danger', 'Aucun utilisateur trouvé pour ce matricule.');
                return $this->redirectToRoute('admin_add_responsable');
            }
            if (in_array($user->getResponsableTicket(), $ticketResponsables)){
                $this->addFlash('danger', 'Utilisateur déjà ajouté.');
                return $this->redirectToRoute('admin_add_responsable');
            }

            $sum=0;

            $tickets = $ticketRepository->findAll();
            foreach ($tickets as $ticket){
                $sum+=$ticket->getQte();
            }

            foreach ($ticketResponsables as $responsable){
                $sum-=$responsable->getQte();
            }

            if ($sum-$form->get('qte')->getData() < 0){
                $this->addFlash('danger', 'La quantité est suppérieure au nombre de tickets disponibles.');
                return $this->redirectToRoute('admin_add_responsable');
            }
            
            $ticketResponsable->setResponsable($user);
            $ticketResponsable->setQte($form->get('qte')->getData());
            $entityManager->persist($ticketResponsable);
            $entityManager->flush();
            $this->addFlash('success', 'Le Resoonsable a été ajouté avec succès.');
            return $this->redirectToRoute('admin_tickets_responsable');
        }

        return $this->render('ticket/add_ticketResponsable.html.twig', [
            'ticketResponsable' => $ticketResponsable,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ticket/responsable/delete/{id}', name:'admin_delete_responsable')]
    public function deleteResponsable(Request $request, int $id, TicketRepository $ticketRepository, ResponsableTicketRepository $ticketResponsableRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have acces to this page.');
        }
        $ticketResponsable = $ticketResponsableRepository->find($id);
        if (!$ticketResponsable) {
            $this->addFlash('danger', 'Le responsable n\'est pas trouvé.');
            return $this->redirectToRoute('admin_tickets_responsable');
        }

        if ($ticketResponsable->getQteVente() > 0){
            $this->addFlash('danger', 'Ce responsable a déjà fait des ventes. Vous ne pouvez pas le supprimer.');
            return $this->redirectToRoute('admin_tickets_responsable');
        }

        $entityManager->remove($ticketResponsable);
        $entityManager->flush();

        $this->addFlash('success', 'Le responsable a été supprimé avec succès');
        return $this->redirectToRoute('admin_tickets_responsable');
    }

    

    #[IsGranted('ROLE_USER')]
    #[Route('/tickets/users', name: 'admin_tickets_user')]
    public function userTickets(Request $request, UserTicketRepository $userTicketRepository, UserRepository $userRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10;
        $matricule_adherent = $request->query->get('matricule_adherent');
        $matricule_responsable = $request->query->get('matricule_responsable');

        $queryBuilder = $userTicketRepository->createQueryBuilder('t')
            ->leftJoin('t.user', 'u')
            ->addSelect('u')
            ->leftJoin('t.responsable', 'r')
            ->addSelect('r')
            ->leftJoin('r.responsable', 'ur')
            ->addSelect('ur');


        if ($matricule_adherent) {
            $queryBuilder->andWhere('u.matricule = :matricule_adherent')
                ->setParameter('matricule_adherent', $matricule_adherent);
        }

        if ($matricule_responsable) {
            $queryBuilder->andWhere('ur.matricule = :matricule_responsable')
                ->setParameter('matricule_responsable', $matricule_responsable);
        }

        $query =  $queryBuilder->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery();

        $userTickets = new Paginator($query, true);


        return $this->render('ticket/user_ticket.html.twig', [
            'userTickets' => $userTickets,
            'currentPage' => $page,
            'totalPages' => ceil(count($userTickets) / $pageSize),
        ]);
    }








    #[IsGranted('ROLE_USER')]
    #[Route('/responsable/add/user', name:'responsable_add_user')]
    public function addUser(Request $request, UserRepository $userRepository, TicketRepository $ticketRepository, ResponsableTicketRepository $responsableTicketRepository, UserTicketRepository $userTicketRepository, EntityManagerInterface $entityManager): Response
    {
        $responsable = $this->getUser()->getResponsableTicket();
        if (!in_array($responsable, $responsableTicketRepository->findAll())){
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette page.');
        }
        $ticketUsers = $userTicketRepository->findAll();
        
        $userTicket = new UserTicket();
        $tickets = $ticketRepository->findAll();
        $prixUnitaire = $tickets ? $tickets[0]->getPrixUnitaire() : 0;
        $userTicket->setPrixUnitaire($prixUnitaire);
        $userTicket->setResponsable($responsable);
        $userTicket->setDateSaisie(new \DateTime());
        $userTicket->setModeEcheance('1');
        $userTicket->setCodeOpposition('1031');

        $form = $this->createForm(UserTicketType::class,$userTicket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]);
            if (!$user){
                $this->addFlash('danger', 'Aucun utilisateur trouvé pour ce matricule.');
                return $this->redirectToRoute('responsable_add_user');
            }

            if ($responsable->getQte() - $responsable->getQteVente() < $form->get('nombre')->getData()){
                $this->addFlash('danger', 'La quantité est suppérieure au nombre de tickets disponibles.');
                return $this->redirectToRoute('responsable_add_user');
            }
            
            if ($form->get('avance')->getData() > $form->get('nombre')->getData() * $prixUnitaire){
                $this->addFlash('danger', 'L\'avance est suppérieure au total.');
                return $this->redirectToRoute('responsable_add_user');
            }
            $userTicket->setNombre($form->get('nombre')->getData());
            $userTicket->setTotal($form->get('nombre')->getData() * $prixUnitaire);
            $userTicket->setAvance($form->get('avance')->getData());
            $userTicket->setNbMois($form->get('nbMois')->getData());
            $userTicket->setModeEcheance($form->get('modeEcheance')->getData());
            $userTicket->setCodeOpposition($form->get('codeOpposition')->getData());
            $userTicket->setDateDebut($form->get('dateDebut')->getData());
            $userTicket->setUser($user);
            
            $entityManager->persist($userTicket);

            $responsable->setQteVente($responsable->getQteVente() + $form->get('nombre')->getData());
            $responsable->setTotalVente($responsable->getTotalVente() + ($form->get('nombre')->getData() * $prixUnitaire));
            $responsable->setTotalAvance($responsable->getTotalAvance() + $form->get('avance')->getData());
            
            $entityManager->persist($responsable);

            $tickets[0]->setQteVente($tickets[0]->getQteVente() + $form->get('nombre')->getData());
            $tickets[0]->setTotalVente($tickets[0]->getTotalVente() + ($form->get('nombre')->getData() * $prixUnitaire));
            $tickets[0]->setTotalAvance($tickets[0]->getTotalAvance() + $form->get('avance')->getData());
            $entityManager->persist($tickets[0]);

            
            $entityManager->flush();
            $this->addFlash('success', 'L\'achat a été ajouté avec succès.');
            return $this->redirectToRoute('admin_tickets_user', [
                'matricule_responsable' => $this->getUser()->getMatricule(),
            ]);
        }

        return $this->render('ticket/add_user.html.twig', [
            'userTicket' => $userTicket,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_User')]
    #[Route('/responsable/edit/user/{id}', name:'responsable_edit_user')]
    public function editUser(LoggerInterface $logger, Request $request, int $id, UserRepository $userRepository, TicketRepository $ticketRepository, ResponsableTicketRepository $responsableTicketRepository, UserTicketRepository $userTicketRepository, EntityManagerInterface $entityManager): Response
    {
        $tickets = $ticketRepository->findAll();
        $responsable = $this->getUser()->getResponsableTicket();
        if (!in_array($responsable, $responsableTicketRepository->findAll())){
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette page.');
        }

        $userTicket = $userTicketRepository->find($id);
        if (!$userTicket){
            $this->addFlash('danger', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('responsable_edit_user', ['id' => $id]);
        }

        $prixUnitaire = $userTicket->getPrixUnitaire();
        $dateSaisie = $userTicket->getDateSaisie();
        $total = $userTicket->getTotal();
        $avance = $userTicket->getAvance();
        $nombre = $userTicket->getNombre();


        $ticketUsers = $userTicketRepository->findAll();

        $form = $this->createForm(UserTicketType::class,$userTicket, [
            'matricule' => $userTicket->getUser() ? $userTicket->getUser()->getMatricule() : '',
        ]);
        $form->handleRequest($request);

        $date = new \DateTime();

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]);
            if (!$user){
                $this->addFlash('danger', 'Aucun utilisateur trouvé pour ce matricule.');
                return $this->redirectToRoute('responsable_edit_user', ['id' => $id]);
            }
            $logger->info('TEST', ['date saisie' => $dateSaisie, 'date' => $date]);
            if ($dateSaisie->format('d-m-Y') == $date->format('d-m-Y')){
                $logger->info('TEST', ['responsable' => $responsable->getQte() - ($responsable->getQteVente() -$nombre), 'form' => $form->get('nombre')->getData()]);
                if ($responsable->getQte() - ($responsable->getQteVente() -$nombre) < $form->get('nombre')->getData()){
                    $this->addFlash('danger', 'La quantité est suppérieure au nombre de tickets disponibles.');
                    return $this->redirectToRoute('responsable_edit_user', ['id' => $id]);
                }
                if ($form->get('avance')->getData() > $form->get('nombre')->getData() * $prixUnitaire){
                    $this->addFlash('danger', 'L\'avance est suppérieure au total.');
                    return $this->redirectToRoute('responsable_edit_user', ['id' => $id]);
                }
                $userTicket->setNombre($form->get('nombre')->getData());
                $userTicket->setTotal($form->get('nombre')->getData() * $prixUnitaire);
                $userTicket->setAvance($form->get('avance')->getData());

                $responsable->setQteVente($responsable->getQteVente() - $nombre + $form->get('nombre')->getData());
                $responsable->setTotalVente($responsable->getTotalVente() - $total + ($form->get('nombre')->getData() * $prixUnitaire));
                $responsable->setTotalAvance($responsable->getTotalAvance() -$avance + $form->get('avance')->getData());

                $entityManager->persist($responsable);

                $tickets[0]->setQteVente($tickets[0]->getQteVente() - $nombre + $form->get('nombre')->getData());
                $tickets[0]->setTotalVente($tickets[0]->getTotalVente() - $total + ($form->get('nombre')->getData() * $prixUnitaire));
                $tickets[0]->setTotalAvance($tickets[0]->getTotalAvance() -$avance + $form->get('avance')->getData());
                $entityManager->persist($tickets[0]);
            
            }
            
            $userTicket->setNbMois($form->get('nbMois')->getData());
            $userTicket->setModeEcheance($form->get('modeEcheance')->getData());
            $userTicket->setCodeOpposition($form->get('codeOpposition')->getData());
            $userTicket->setDateDebut($form->get('dateDebut')->getData());
            $userTicket->setUser($user);

            $entityManager->persist($userTicket);
            $entityManager->flush();
            $this->addFlash('success', 'L\'achat a été modifié avec succès.');
            return $this->redirectToRoute('admin_tickets_user', [
                'matricule_responsable' => $this->getUser()->getMatricule(),
            ]);
        }

        return $this->render('ticket/edit_user.html.twig', [
            'userTicket' => $userTicket,
            'date' => $date,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/responsable/delete/user/{id}', name:'responsable_delete_user')]
    public function deleteUser(int $id, TicketRepository $ticketRepository, ResponsableTicketRepository $responsableTicketRepository, UserTicketRepository $userTicketRepository, EntityManagerInterface $entityManager): Response
    {
        $tickets = $ticketRepository->findAll();
        $responsable = $this->getUser()->getResponsableTicket();
        if (!in_array($responsable, $responsableTicketRepository->findAll())){
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette page.');
        }

        $userTicket = $userTicketRepository->find($id);
        if (!$userTicket){
            $this->addFlash('danger', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('responsable_delete_user');
        }

        $responsable->setQteVente($responsable->getQteVente() - $userTicket->getNombre());
        $responsable->setTotalVente($responsable->getTotalVente() - ($userTicket->getNombre() * $userTicket->getPrixUnitaire()));
        $responsable->setTotalAvance($responsable->getTotalAvance() -$userTicket->getAvance());

        $entityManager->persist($responsable);

        $tickets[0]->setQteVente($tickets[0]->getQteVente() - $userTicket->getNombre());
        $tickets[0]->setTotalVente($tickets[0]->getTotalVente() - ($userTicket->getNombre() * $userTicket->getPrixUnitaire()));
        $tickets[0]->setTotalAvance($tickets[0]->getTotalAvance() -$userTicket->getAvance());
        $entityManager->persist($tickets[0]);

        $entityManager->remove($userTicket);
        $entityManager->flush();

        $this->addFlash('success', 'Suppression faite avec succés');
        return $this->redirectToRoute('admin_tickets_user', [
            'matricule_responsable' => $this->getUser()->getMatricule(),
        ]);
    }



}
