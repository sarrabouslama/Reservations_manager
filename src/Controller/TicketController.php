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
use Symfony\Component\HttpFoundation\JsonResponse;




class TicketController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/tickets', name: 'admin_tickets')]
    public function index(TicketRepository $ticketRepository, EntityManagerInterface $entityManager): Response
    {
        $tickets = $ticketRepository->findAll();
        return $this->render('ticket/tickets.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/tickets/add', name: 'admin_add_ticket')]
    public function addTicket(Request $request, TicketRepository $ticketRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ticket->setQte($form->get('qte')->getData());
            $ticket->setPrixUnitaire($form->get('prixUnitaire')->getData());
            $ticket->setLocalisation($form->get('localisation')->getData());
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('tickets_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Une erreur est survenue lors du téléchargement de l\'image');
                    return $this->redirectToRoute('admin_add_ticket');
                }

                // Delete old image if exists
                if ($ticket->getImage()) {
                    $oldImagePath = $this->getParameter('tickets_images_directory').'/'.$ticket->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $ticket->setImage($newFilename);
            } 
            $entityManager->persist($ticket);
            $entityManager->flush();
            $this->addFlash('success', 'Le ticket a été mis à jour avec succès.');
            return $this->redirectToRoute('admin_tickets');
        }

        return $this->render('ticket/add_ticket.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/tickets/edit/{id}', name: 'admin_edit_ticket')]
    public function editTicket(Request $request, int $id, TicketRepository $ticketRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        $ticket = $ticketRepository->find($id);
        if (!$ticket) {
            $this->addFlash('danger', 'Veillez choisir une catégorie de tickets.');
            return $this->redirectToRoute('admin_tickets');
        }

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($ticket->getResponsableTickets()){
                $qte = 0;
                foreach($ticket->getResponsableTickets() as $responsable){
                    $qte+=$responsable->getQte();
                }
                if ($qte > $form->get('qte')->getData()){
                    $this->addFlash('danger', 'Nombre de tickets insuffosant. Des responsables ont déjà acquis des tickets');
                    return $this->redirectToRoute('admin_edit_ticket', ['id' => $id]);
                }
            }
            $ticket->setQte($form->get('qte')->getData());
            $ticket->setPrixUnitaire($form->get('prixUnitaire')->getData());
            $ticket->setLocalisation($form->get('localisation')->getData());
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('tickets_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Une erreur est survenue lors du téléchargement de l\'image');
                    return $this->redirectToRoute('admin_edit_ticket', ['id' => $id]);
                }

                // Delete old image if exists
                if ($ticket->getImage()) {
                    $oldImagePath = $this->getParameter('tickets_images_directory').'/'.$ticket->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $ticket->setImage($newFilename);
            } 
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

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/tickets/delete/{id}', name: 'admin_delete_ticket')]
    public function deleteTicket(Request $request, int $id, TicketRepository $ticketRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        $ticket = $ticketRepository->find($id);
        if (!$ticket) {
            $this->addFlash('danger', 'Veillez choisir une catégorie de tickets.');
            return $this->redirectToRoute('admin_tickets');
        }
        $responsables = $ticket->getResponsableTickets();
        foreach ($responsables as $responsable){
            if (count($responsable->getUserTickets()) > 0){
                $this->addFlash('danger', 'Vous ne pouvez pas supprimer cette catégorie. Des achats ont été effectués');
                return $this->redirectToRoute('admin_tickets');
            }

        }


        $entityManager->remove($ticket);
        $entityManager->flush();

        return $this->redirectToRoute('admin_tickets');
    }




    #[IsGranted('ROLE_USER')]
    #[Route('/tickets/responsable', name: 'admin_tickets_responsable')]
    public function responsableTickets(Request $request, ResponsableTicketRepository $responsableTicketRepository, UserTicketRepository $userTicketRepository, TicketRepository $ticketRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10;
        $matricule = $request->query->get('matricule');
        $localisation = $request->query->get('localisation');

        $queryBuilder = $responsableTicketRepository->createQueryBuilder('rt')
            ->leftJoin('rt.responsable', 'u')
            ->addSelect('u')
            ->leftJoin('rt.ticket', 't')
            ->addSelect('t');


        if ($matricule) {
            $queryBuilder->andWhere('u.matricule = :matricule')
                ->setParameter('matricule', $matricule);
        }
        
        if ($localisation) {
            $queryBuilder->andWhere('t.localisation = :localisation')
                ->setParameter('localisation', $localisation);
        }

        $query =  $queryBuilder->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery();

        $responsableTickets = new Paginator($query, true);

        $grouped = [];
        foreach ($responsableTickets as $rt) {
            $grouped[$rt->getTicket()->getLocalisation()][] = $rt;
        }
        
        return $this->render('ticket/responsable_tickets.html.twig', [
            'groupedResponsableTickets' => $grouped,
            'responsableTickets' => $responsableTickets,
            'currentPage' => $page,
            'totalPages' => ceil(count($responsableTickets) / $pageSize),
            'allLocalisations' => $ticketRepository->findAllLocalisations(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ticket/responsable/{id}', name:'admin_edit_responsable', requirements: ['id' => '\\d+'])]
    public function editResponsable(Request $request, int $id, UserRepository $userRepository, TicketRepository $ticketRepository, ResponsableTicketRepository $responsableTicketRepository, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        $ticketResponsable = $responsableTicketRepository->find($id);
        if (!$ticketResponsable) {
            $this->addFlash('danger', 'Le responsable n\'est pas trouvé.');
            return $this->redirectToRoute('admin_tickets_responsable');
        }
        $qte = $ticketResponsable->getQte();
        $localisation = $ticketResponsable->getTicket()->getLocalisation();
        
        
        $form = $this->createForm(ResponsableTicketType::class, $ticketResponsable, [
            'matricule' => $ticketResponsable->getResponsable() ? $ticketResponsable->getResponsable()->getMatricule() : '',
            'localisation' => $ticketResponsable->getTicket() ? $ticketResponsable->getTicket()->getLocalisation() : '',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ticket = $ticketRepository->findOneBy(['localisation' => $form->get('localisation')->getData()]);
            $ticketResponsables = $responsableTicketRepository->findBy(['ticket' => $ticket]);

            if ($form->get('matricule')->getData() !== $ticketResponsable->getResponsable()->getMatricule()){
                $user = $userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]);
                if (!$user){
                    $this->addFlash('danger', 'Aucun reponsable trouvé pour ce matricule.');
                    return $this->redirectToRoute('admin_edit_responsable', [
                        'id'=>$id,
                    ]);
                }
                if (in_array($user->getResponsableTickets(), $ticketResponsables)){
                    $this->addFlash('danger', 'reponsable déjà ajouté.');
                    return $this->redirectToRoute('admin_edit_responsable', [
                        'id'=>$id,
                    ]);
                }
            }

            if ($form->get('qte')->getData() !== $qte){
                $sum=$ticket->getQte();;                

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

            if ($localisation !== $form->get('localisation')->getData()){
                if (count($ticketResponsable->getUserTickets()) > 0){
                    $this->addFlash('danger', "Vous ne pouvez pas modifier la localisation. Des tickets ont été vendus");
                    return $this->redirectToRoute('admin_edit_responsable', [
                        'id'=>$id,
                    ]);
                }
            }

            $ticketResponsable->setResponsable($userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]));
            $ticketResponsable->setQte($form->get('qte')->getData());
            $ticketResponsable->setTicket($ticket);
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
    public function addResponsable(Request $request, UserRepository $userRepository, TicketRepository $ticketRepository, ResponsableTicketRepository $responsableTicketRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        
        $ticketResponsable = new ResponsableTicket();
        
        $form = $this->createForm(ResponsableTicketType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ticket = $ticketRepository->findOneBy(['localisation' => $form->get('localisation')->getData()]);
            $ticketResponsables = $responsableTicketRepository->findBy(['ticket' => $ticket]);
            
            $user = $userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]);
            if (!$user){
                $this->addFlash('danger', 'Aucun responsable trouvé pour ce matricule.');
                return $this->redirectToRoute('admin_add_responsable');
            }
            if (in_array($user->getResponsableTickets(), $ticketResponsables)){
                $this->addFlash('danger', 'responsable déjà ajouté.');
                return $this->redirectToRoute('admin_add_responsable');
            }
            
            $sum=$ticket->getQte();
            
            foreach ($ticketResponsables as $responsable){
                $sum-=$responsable->getQte();
            }
            
            if ($sum-$form->get('qte')->getData() < 0){
                $this->addFlash('danger', 'La quantité est suppérieure au nombre de tickets disponibles.');
                return $this->redirectToRoute('admin_add_responsable');
            }
            
            $ticketResponsable->setResponsable($user);
            $ticketResponsable->setQte($form->get('qte')->getData());
            $ticketResponsable->setTicket($ticket);
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
    public function deleteResponsable(Request $request, int $id, TicketRepository $ticketRepository, ResponsableTicketRepository $responsableTicketRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        $ticketResponsable = $responsableTicketRepository->find($id);
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
    public function userTickets(Request $request, UserTicketRepository $userTicketRepository, UserRepository $userRepository, TicketRepository $ticketRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10;
        $matricule_adherent = $request->query->get('matricule_adherent');
        $matricule_responsable = $request->query->get('matricule_responsable');
        $localisation = $request->query->get('localisation');

        $queryBuilder = $userTicketRepository->createQueryBuilder('ut')
            ->leftJoin('ut.user', 'u')
            ->addSelect('u')
            ->leftJoin('ut.responsable', 'r')
            ->addSelect('r')
            ->leftJoin('r.ticket', 't')
            ->addSelect('t')
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

        if ($localisation) {
            $queryBuilder->andWhere('t.localisation = :localisation')
                ->setParameter('localisation', $localisation);
        }

        $query =  $queryBuilder->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery();

        $userTickets = new Paginator($query, true);


        return $this->render('ticket/user_ticket.html.twig', [
            'userTickets' => $userTickets,
            'currentPage' => $page,
            'totalPages' => ceil(count($userTickets) / $pageSize),
            'allLocalisations' => $ticketRepository->findAllLocalisations(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/responsable/add/user', name:'responsable_add_user')]
    public function addUser(Request $request, UserRepository $userRepository, TicketRepository $ticketRepository, ResponsableTicketRepository $responsableTicketRepository, UserTicketRepository $userTicketRepository, EntityManagerInterface $entityManager): Response
    {
        if (count($this->getUser()->getResponsableTickets()->toArray()) == 0){
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette page.');
        }
        
        $userTicket = new UserTicket();
        $userTicket->setDateSaisie(new \DateTime());
        $userTicket->setModeEcheance('1');
        $userTicket->setCodeOpposition('1031');
        $userTicket->setDateDebut(new \DateTime('2025-09-01'));
        $userTicket->setNbMois(6);
        $form = $this->createForm(UserTicketType::class,$userTicket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]);
            if (!$user){
                $this->addFlash('danger', 'Aucun utilisateur trouvé pour ce matricule.');
                return $this->redirectToRoute('responsable_add_user');
            }

            $ticket = $ticketRepository->findOneBy(['localisation' => $form->get('localisation')->getData()]);
            $responsable = $responsableTicketRepository->findOneBy(['responsable' => $this->getUser() , 'ticket' => $ticket]);
            
            if(!$responsable){
                $this->addFlash('danger', 'Vous n\'avez pas de tickets dans '. $form->get('localisation')->getData());
                return $this->redirectToRoute('responsable_add_user');
            }
            if ($responsable->getQte() - $responsable->getQteVente() < $form->get('nombre')->getData()){
                $this->addFlash('danger', 'La quantité est suppérieure au nombre de tickets disponibles.');
                return $this->redirectToRoute('responsable_add_user');
            }
            
            if ($form->get('avance')->getData() > $form->get('nombre')->getData() * $ticket->getPrixUnitaire()){
                $this->addFlash('danger', 'L\'avance est suppérieure au total.');
                return $this->redirectToRoute('responsable_add_user');
            }
            
            $userTicket->setResponsable($responsable);
            $userTicket->setPrixUnitaire($ticket->getPrixUnitaire());
            $userTicket->setNombre($form->get('nombre')->getData());
            $userTicket->setTotal($form->get('nombre')->getData() * $ticket->getPrixUnitaire());
            $userTicket->setAvance($form->get('avance')->getData());
            $userTicket->setNbMois($form->get('nbMois')->getData());
            $userTicket->setModeEcheance($form->get('modeEcheance')->getData());
            $userTicket->setCodeOpposition($form->get('codeOpposition')->getData());
            $userTicket->setDateDebut($form->get('dateDebut')->getData());
            $userTicket->setUser($user);
            
            $entityManager->persist($userTicket);

            $responsable->setQteVente($responsable->getQteVente() + $form->get('nombre')->getData());
            $responsable->setTotalVente($responsable->getTotalVente() + ($form->get('nombre')->getData() * $ticket->getPrixUnitaire()));
            $responsable->setTotalAvance($responsable->getTotalAvance() + $form->get('avance')->getData());
            
            $entityManager->persist($responsable);

            $ticket->setQteVente($ticket->getQteVente() + $form->get('nombre')->getData());
            $ticket->setTotalVente($ticket->getTotalVente() + ($form->get('nombre')->getData() * $ticket->getPrixUnitaire()));
            $ticket->setTotalAvance($ticket->getTotalAvance() + $form->get('avance')->getData());
            $entityManager->persist($ticket);

            
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
        $userTicket = $userTicketRepository->find($id);
        if (!$userTicket){
            $this->addFlash('danger', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('responsable_edit_user', ['id' => $id]);
        }

        if ($userTicket->getResponsable()->getResponsable() !== $this->getUser()){
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette page.');
        }

        $dateSaisie = $userTicket->getDateSaisie();
        $total = $userTicket->getTotal();
        $avance = $userTicket->getAvance();
        $nombre = $userTicket->getNombre();
        $oldResponsable = $userTicket->getResponsable();

        $form = $this->createForm(UserTicketType::class,$userTicket, [
            'matricule' => $userTicket->getUser() ? $userTicket->getUser()->getMatricule() : '',
            'localisation' => $userTicket->getResponsable()->getTicket() ? $userTicket->getResponsable()->getTicket()->getLocalisation() : '',
        ]);
        $form->handleRequest($request);

        $date = new \DateTime();

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]);
            if (!$user){
                $this->addFlash('danger', 'Aucun adhérent trouvé pour ce matricule.');
                return $this->redirectToRoute('responsable_edit_user', ['id' => $id]);
            }
            if ($dateSaisie->format('d-m-Y') == $date->format('d-m-Y')){
                $ticket = $ticketRepository->findOneBy(['localisation' => $form->get('localisation')->getData()]);
                $responsable = $responsableTicketRepository->findOneBy(['responsable' => $this->getUser() , 'ticket' => $ticket]);

                if ($responsable->getQte() - ($responsable->getQteVente() -$nombre) < $form->get('nombre')->getData()){
                    $this->addFlash('danger', 'La quantité est suppérieure au nombre de tickets disponibles.');
                    return $this->redirectToRoute('responsable_edit_user', ['id' => $id]);
                }
                if ($form->get('avance')->getData() > $form->get('nombre')->getData() * $ticket->getPrixUnitaire()){
                    $this->addFlash('danger', 'L\'avance est suppérieure au total.');
                    return $this->redirectToRoute('responsable_edit_user', ['id' => $id]);
                }

                $userTicket->setResponsable($responsable);
                $userTicket->setNombre($form->get('nombre')->getData());
                $userTicket->setTotal($form->get('nombre')->getData() * $ticket->getPrixUnitaire());
                $userTicket->setAvance($form->get('avance')->getData());
                $userTicket->setPrixUnitaire($ticket->getPrixUnitaire());

                $oldResponsable->setQteVente($oldResponsable->getQteVente() - $nombre);
                $oldResponsable->setTotalVente($oldResponsable->getTotalVente() - $total);
                $oldResponsable->setTotalAvance($oldResponsable->getTotalAvance() -$avance);
                $entityManager->persist($oldResponsable);

                $responsable->setQteVente($responsable->getQteVente() + $form->get('nombre')->getData());
                $responsable->setTotalVente($responsable->getTotalVente() + ($form->get('nombre')->getData() * $ticket->getPrixUnitaire()));
                $responsable->setTotalAvance($responsable->getTotalAvance() + $form->get('avance')->getData());

                $entityManager->persist($responsable);

                $oldResponsable->getTicket()->setQteVente($oldResponsable->getTicket()->getQteVente() - $nombre);
                $oldResponsable->getTicket()->setTotalVente($oldResponsable->getTicket()->getTotalVente() - $total);
                $oldResponsable->getTicket()->setTotalAvance($oldResponsable->getTicket()->getTotalAvance() - $avance);
                $entityManager->persist($oldResponsable->getTicket());

                $ticket->setQteVente($ticket->getQteVente() + $form->get('nombre')->getData());
                $ticket->setTotalVente($ticket->getTotalVente() + ($form->get('nombre')->getData() * $ticket->getPrixUnitaire()));
                $ticket->setTotalAvance($ticket->getTotalAvance() + $form->get('avance')->getData());
                $entityManager->persist($ticket);
            
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
        $userTicket = $userTicketRepository->find($id);
        if (!$userTicket){
            $this->addFlash('danger', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('responsable_delete_user');
        }
        $responsable = $userTicket->getResponsable();
        if (!in_array($responsable, $this->getUser()->getResponsableTickets()->toArray())){
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette page.');
        }
        
        $ticket = $responsable->getTicket();

        $responsable->setQteVente($responsable->getQteVente() - $userTicket->getNombre());
        $responsable->setTotalVente($responsable->getTotalVente() - ($userTicket->getNombre() * $userTicket->getPrixUnitaire()));
        $responsable->setTotalAvance($responsable->getTotalAvance() -$userTicket->getAvance());

        $entityManager->persist($responsable);

        $ticket->setQteVente($ticket->getQteVente() - $userTicket->getNombre());
        $ticket->setTotalVente($ticket->getTotalVente() - ($userTicket->getNombre() * $userTicket->getPrixUnitaire()));
        $ticket->setTotalAvance($ticket->getTotalAvance() -$userTicket->getAvance());
        $entityManager->persist($ticket);

        $entityManager->remove($userTicket);
        $entityManager->flush();

        $this->addFlash('success', 'Suppression faite avec succés');
        return $this->redirectToRoute('admin_tickets_user', [
            'matricule_responsable' => $this->getUser()->getMatricule(),
        ]);
    }


    #[Route('/ticket/by-localisation/{localisation}', name: 'ticket_by_localisation')]
    public function imageByLocalisation(string $localisation, TicketRepository $ticketRepository): JsonResponse
    {
        $ticket = $ticketRepository->findOneBy(['localisation' => $localisation]);
        if (!$ticket) {
            return new JsonResponse(['error' => 'Ticket not found'], 404);
        }
        return new JsonResponse([
            'prix' => $ticket->getPrixUnitaire(),
            'image' => $ticket->getImage(),
        ]);
    }

}
