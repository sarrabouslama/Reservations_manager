<?php

namespace App\Controller;

use App\Entity\Home;
use App\Entity\Reservation;
use App\Entity\HomePeriod;
use App\Entity\Notification;
use App\Entity\User;
use App\Form\ContactInfoType;
use App\Repository\HomePeriodRepository;
use App\Repository\ReservationRepository;
use App\Repository\HomeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\PiscineReservationRepository;

#[Route('/reservations')]
class ReservationController extends AbstractController
{
    #[Route('/user/reservation/{id}', name: 'app_user_reservation')]
    #[IsGranted('ROLE_USER','ROLE_ADMIN','ROLE_SEMIADMIN')]
    public function reservation(int $id, ReservationRepository $reservationRepository, UserRepository $userRepository, PiscineReservationRepository $piscineReservationRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $user = $userRepository->find($id);
            if (!$user) {
                throw $this->createNotFoundException('User not found');
            }
        }
        else{
            $user = $this->getUser();
            if (!$user) {
                throw $this->createNotFoundException('User not found');
            }
            if ($user->getId() !== $id) {
                throw $this->createAccessDeniedException('You do not have access to this page.');
            }
        }
        $reservation = $reservationRepository->findOneBy(['user' => $user]);
        $piscineReservation = $piscineReservationRepository->findOneBy(['user' => $user]);
        return $this->render('user/reservation.html.twig', [
            'reservation' => $reservation,
            'piscineReservation' => $piscineReservation,
        ]);
    }





    #[Route('/new/{id}', name: 'app_reservation_new', methods: ['POST','GET'])]
    #[IsGranted('ROLE_USER','ROLE_ADMIN','ROLE_SEMIADMIN')]
    public function new(
        Request $request, 
        int $id, 
        EntityManagerInterface $entityManager, 
        ReservationRepository $reservationRepository,
        HomePeriodRepository $homePeriodRepository,
        HomeRepository $homeRepository
    ): Response {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Les administrateurs ne peuvent pas faire de réservations.');
        }
        $user = $this->getUser();
        if (!$user->getTel()) {
            $form = $this->createForm(ContactInfoType::class, [
                'email' => $user->getEmail(),
                'tel' => $user->getTel(),
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $user->setEmail($data['email']);
                $user->setTel($data['tel']);
                $entityManager->persist($user);
                $entityManager->flush();
                // Redirect to the same route to continue reservation
                return $this->redirectToRoute('app_reservation_new', [
                    'id' => $id,
                    'dateDebut' => $request->get('dateDebut'),
                    'dateFin' => $request->get('dateFin'),
                ]);
            }

            return $this->render('reservation/contact_info.html.twig', [
                'form' => $form->createView(),
            ]);
        }
        $dateDebutStr = $request->get('dateDebut');
        $dateFinStr = $request->get('dateFin');

        $dateDebut = $dateDebutStr ? \DateTime::createFromFormat('d/m/Y', $dateDebutStr) : null;
        $dateFin = $dateFinStr ? \DateTime::createFromFormat('d/m/Y', $dateFinStr) : null;

        $home = $homeRepository->find($id);
        if (!$home) {
            throw $this->createNotFoundException('Home not found');
        }
        // Check if there's an available period for these dates
        $homePeriod = $homePeriodRepository->findOneBy([
            'home' => $home,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ]);

        if (!$homePeriod) {
            $this->addFlash('danger', 'Aucune période disponible pour ces dates.');
            return $this->redirectToRoute('app_home_show', ['id' => $home->getId()]);
        }
        
        // Delete any existing reservations for this user
        $userReservation = $user->getReservation();
        if ($userReservation){
            if ($userReservation->getHomePeriod() === $homePeriod) {
                $this->addFlash('info', 'Vous avez déjà réservé cette maison.');
                return $this->redirectToRoute('app_home_show', ['id' => $home->getId()]);
            }
            else{
                $nom = $userReservation->getHomePeriod()->getHome()->getNom();
                $this->addFlash('info', 'Votre ancienne réservation à ' . $nom .' a été annulée.');
                $reservationRepository->deleteUserReservations($user);
                $entityManager->flush();
            }
            
        }
        
        // Create a new reservation
        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setHomePeriod($homePeriod);

        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été enregistrée avec succès.');
        
        return $this->redirectToRoute('app_home_show', ['id' => $home->getId()]);
    } 
    

    #[Route('/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER','ROLE_ADMIN','ROLE_SEMIADMIN')]
    public function cancel(
        int $id, 
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        HomePeriodRepository $homePeriodRepository
    ): Response {
        
        if ($this->isGranted('ROLE_ADMIN')) {
            $residence = $request->query->get('residence');
            $region = $request->query->get('region');
            $nombreChambres = $request->query->get('nombreChambres');
        }
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée.');
        }
        if ($this->getUser() !== $reservation->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas annuler cette réservation.');
        }
        $ResName = $reservation->getHomePeriod()->getHome()->getNom();
        $nom = $reservation->getUser()->getNom();

        if ($reservation->isSelected()) {
            //notifier l'admin 
            $notification = new Notification();
            $notification->setMessage('La réservation de ' . $reservation->getUser()->getNom() . ' a été annulée.');
            $notification->setCreatedAt(new \DateTime());
            $notification->setType('admin');
            $entityManager->persist($notification);
            $entityManager->flush();
        }
        if ($this->isGranted('ROLE_ADMIN')) {
            // notifier l'utilisateur
            $notification = new Notification();
            $notification->setMessage('Votre réservation pour ' . $ResName . ' a été annulée par un administrateur.');
            $notification->setUser($reservation->getUser());
            $notification->setCreatedAt(new \DateTime());
            $notification->setType('user');
            $entityManager->persist($notification);
            $entityManager->flush();
        }
        
        $entityManager->remove($reservation);
        $entityManager->flush();
        
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('success', 'La réservation de ' . $nom . ' à '.$ResName. ' a été annulée avec succès.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
            ]);
        }
        $this->addFlash('success', 'Votre réservation à '. $ResName.' a été supprimée avec succès.');
        return $this->redirectToRoute('app_home_index');
    }

    #[Route('/{id}/upload-receipt', name: 'app_upload_receipt', methods: ['POST'])]
    #[IsGranted('ROLE_USER','ROLE_ADMIN','ROLE_SEMIADMIN')]
    public function uploadReceipt(int $id, Request $request, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager): Response
    {
        $reservation = $reservationRepository->find($id);
        if (!$reservation || $reservation->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Réservation non trouvée ou accès refusé.');
        }
        if (!$reservation->isSelected()) {
            $this->addFlash('danger', 'Vous ne pouvez importer un reçu que pour une réservation sélectionnée.');
            return $this->redirectToRoute('user_reservations');
        }
        $file = $request->files->get('receipt');
        if ($file) {
            $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg','image/gif','image/heic','image/bmp','image/webp'];
            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                $this->addFlash('danger', 'Format de fichier non supporté.');
                return $this->redirectToRoute('app_user_reservation', ['id' => $this->getUser()->getId()]);
            }
            $filename = 'receipt_' . $reservation->getId() . '_' . uniqid() . '.' . $file->guessExtension();
            $file->move($this->getParameter('receipts_directory'), $filename);
            $reservation->setReceiptFilename($filename);
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Reçu importé avec succès.');
        } else {
            $this->addFlash('danger', 'Aucun fichier reçu.');
        }
        return $this->redirectToRoute('app_user_reservation', ['id' => $this->getUser()->getId()]);
    }

    
    #[Route('/reservations', name: 'admin_reservations', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN','ROLE_SEMIADMIN')]
    public function Reservations(Request $request, ReservationRepository $reservationRepository, HomeRepository $homeRepository, HomePeriodRepository $homePeriodRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SEMIADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10;

        $filters = [
            'region' => $request->query->get('region'),
            'residence' => $request->query->get('residence'),
            'nombreChambres' => $request->query->get('nombreChambres'),
        ];
        $filters = array_filter($filters);
        
        $sort = [
            'field'  => $request->query->get('sortField'),
            'direction' => $request->query->get('sortDirection'),
        ];


        
        $selectedHomePeriodId = $request->query->get('homePeriod');
        $allPeriods = [];
        if (isset($filters['residence'])  && isset($filters['region']) && isset($filters['nombreChambres'])) {
            $homes = $homeRepository->findByFilters($filters,$sort);
            $homeIds = array_map(fn($h) => $h->getId(), $homes);
            if ($homeIds) {
                foreach ($homeIds as $hid) {
                    $allPeriods = array_merge($allPeriods, $homePeriodRepository->findByHome($hid));
                }
            }
        } else {
            $allPeriods = $homePeriodRepository->findAll();
        }
        if (!isset($sort['field']) || !isset($sort['direction'])) {
            
            $session = $request->getSession();
            $shuffledIds = $session->get('shuffled_reservation_ids');
        }

        $reservations = [];
        $totalReservations = 0;
        $totalPages = 1;
        $allReservations = $reservationRepository->findByFilters($filters, $sort);
        if ($selectedHomePeriodId) {
            $allReservations = array_filter($allReservations, function ($reservation) use ($selectedHomePeriodId) {
                return $reservation->getHomePeriod() && $reservation->getHomePeriod()->getId() == $selectedHomePeriodId;
            });
        }
        if (isset($shuffledIds)) {
            $reservationMap = [];
            foreach ($allReservations as $r) {
                $reservationMap[$r->getId()] = $r;
            }
            $orderedReservations = [];
            foreach ($shuffledIds as $id) {
                if (isset($reservationMap[$id])) {
                    $orderedReservations[] = $reservationMap[$id];
                    unset($reservationMap[$id]);
                }
            }
            $orderedReservations = array_merge($orderedReservations, array_values($reservationMap));
            $totalReservations = count($orderedReservations);
            $reservations = array_slice($orderedReservations, ($page - 1) * $pageSize, $pageSize);
        } else {
            $totalReservations = count($allReservations);
            $reservations = array_slice($allReservations, ($page - 1) * $pageSize, $pageSize);
        }
        $totalPages = ceil($totalReservations / $pageSize);

        return $this->render('admin/reservations.html.twig', [
            'reservations' => $reservations,
            'homes' => $homeRepository->findByFilters($filters,$sort),
            'allResidences' => $homeRepository->findAllResidences($filters),
            'allRegions' => $homeRepository->findAllRegions($filters),
            'allNbChambres' => $homeRepository->findAllNbChambres($filters),
            'residence' => $filters['residence'] ?? null,
            'region' => $filters['region'] ?? null,
            'nombreChambres' => $filters['nombreChambres'] ?? null,
            'sortField' => $sort['field'],
            'sortDirection' => $sort['direction'],
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'allPeriods' => $allPeriods,
            'selectedHomePeriodId' => $selectedHomePeriodId,
        ]);
    }

    #[Route('/{id}/select', name: 'app_reservation_select', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN','ROLE_SEMIADMIN')]
    public function select(int $id,Request $request, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10;



        $residence = $request->query->get('residence');
        $region = $request->query->get('region');
        $nombreChambres = $request->query->get('nombreChambres');

        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            $this->addFlash('danger','Réservation non trouvée.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
                'page' => $page,
            ]);
        }
        if ($reservation->isSelected()) {
            $this->addFlash('danger','Cette réservation a déjà été sélectionnée.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
                'page' => $page,
            ]);
        }
        if ($reservation->isConfirmed()) {
            $this->addFlash('danger','Cette réservation est déjà confirmée.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
                'page' => $page,
            ]);
        }
        if ($reservation->getHomePeriod()->getMaxUsers() <= count($reservation->getHomePeriod()->getReservations()->filter(fn($r) => $r->isSelected()))) {
            $this->addFlash('danger','Cette période de réservation est déjà complète.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
                'page' => $page,
            ]);
        }

        $reservation->setIsSelected(true);
        $reservation->setDateSelection(new \DateTime());
        $entityManager->persist($reservation);
        $ResName = $reservation->getHomePeriod()->getHome()->getNom();

        // Notify the user
        $notification = new Notification();
        $notification->setMessage("Votre réservation à {{ $ResName }} a été sélectionnée par l'admin!");
        $notification->setCreatedAt(new \DateTime());
        $notification->setType('user');
        $notification->setUser($reservation->getUser());
        $entityManager->persist($notification);

        $entityManager->flush();

        $filters = [
            'residence' => $residence,
            'region' => $region,
            'nombreChambres' => $nombreChambres,
        ];
        $allReservationsAfterChange = $reservationRepository->findByFilters($filters);
        $finalShuffledIds = $this->getFinalShuffledIds($allReservationsAfterChange);
        $request->getSession()->set('shuffled_reservation_ids', $finalShuffledIds);

        $this->addFlash('success', 'Le réservation a été sélectionnée.');
        return $this->redirectToRoute('admin_reservations', [
            'residence' => $residence,
            'region' => $region,
            'nombreChambres' => $nombreChambres,
            'page' => $page,
        ]);
    }

    #[Route('/{id}/confirm', name: 'app_reservation_confirm', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN','ROLE_SEMIADMIN')]
public function confirm(int $id,Request $request, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10;

        $residence = $request->query->get('residence');
        $region = $request->query->get('region');
        $nombreChambres = $request->query->get('nombreChambres');

        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            $this->addFlash('danger','Réservation non trouvée.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
                'page' => $page,
            ]);
        }
        if (!$reservation->isSelected()) {
            $this->addFlash('danger',"Cette réservation n'est pas sélectionnée.");
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
                'page' => $page,
            ]);
        }
        if ($reservation->isConfirmed()) {
            $this->addFlash('danger','Cette réservation est déjà confirmée.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
                'page' => $page,
            ]);
        }
        $reservation->setIsConfirmed(true);
        $reservation->setDateSelection(new \DateTime());
        $entityManager->persist($reservation);

        // Notify the user
        $notification = new Notification();
        $notification->setMessage('Votre réservation a été confirmée !');
        $notification->setCreatedAt(new \DateTime());
        $notification->setType('user');
        $notification->setUser($reservation->getUser());
        $entityManager->persist($notification);

        $entityManager->flush();

        // Update shuffled_reservation_ids session after any change
        $filters = [
            'residence' => $residence,
            'region' => $region,
            'nombreChambres' => $nombreChambres,
        ];
        $allReservationsAfterChange = $reservationRepository->findByFilters($filters);
        $finalShuffledIds = $this->getFinalShuffledIds($allReservationsAfterChange);
        $request->getSession()->set('shuffled_reservation_ids', $finalShuffledIds);

        $this->addFlash('success', 'Le réservation a été confirmée.');
        return $this->redirectToRoute('admin_reservations', [
            'residence' => $residence,
            'region' => $region,
            'nombreChambres' => $nombreChambres,
            'page' => $page,
        ]);
    }

    #[Route('/{id}/reject', name: 'app_reservation_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN','ROLE_SEMIADMIN')]
    public function reject(
        int $id, 
        EntityManagerInterface $entityManager, 
        ReservationRepository $reservationRepository,
        Request $request
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10;

        $residence = $request->query->get('residence');
        $region = $request->query->get('region');
        $nombreChambres = $request->query->get('nombreChambres');

        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            $this->addFlash('danger','Réservation non trouvée.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
                'page' => $page,
            ]);

        }

        if (!$reservation->isSelected()) {
            $this->addFlash('danger',"Cette réservation n'est pas sélectionée.");
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
                'page' => $page,
            ]);
        }

        $reservation->setIsSelected(false);
        $reservation->setIsConfirmed(false);
        $reservation->setDateSelection(null);
        $entityManager->persist($reservation);

        // Notify the user
        $notification = new Notification();
        $notification->setMessage('Votre réservation a été rejetée.');
        $notification->setCreatedAt(new \DateTime());
        $notification->setType('user');
        $notification->setUser($reservation->getUser());
        $entityManager->persist($notification);

        $entityManager->flush();

        // Update shuffled_reservation_ids session after any change
        $filters = [
            'residence' => $residence,
            'region' => $region,
            'nombreChambres' => $nombreChambres,
        ];
        $allReservationsAfterChange = $reservationRepository->findByFilters($filters);
        $finalShuffledIds = $this->getFinalShuffledIds($allReservationsAfterChange);
        $request->getSession()->set('shuffled_reservation_ids', $finalShuffledIds);

        $this->addFlash('success', 'La réservation a été rejetée.');
        return $this->redirectToRoute('admin_reservations',[
            'residence' => $residence,
            'region' => $region,
            'nombreChambres' => $nombreChambres,
            'page' => $page,
        ]);
    }

    private function getFinalShuffledIds(array $reservations): array
    {
        $periodGroups = [];
        foreach ($reservations as $r) {
            $periodGroups[$r->getHomePeriod()->getId()][] = $r;
        }

        $finalShuffledIds = [];
        foreach ($periodGroups as $periodReservations) {
            $selectedIds = [];
            $notSelectedNotLastYearIds = []; 
            $notSelectedLastYearIds = [];    

            foreach ($periodReservations as $r) {
                if ($r->isSelected()) {
                    $selectedIds[] = $r->getId();
                } else {
                    if ($r->getUser()->isLastYear()) {
                        $notSelectedLastYearIds[] = $r->getId();
                    } else {
                        $notSelectedNotLastYearIds[] = $r->getId();
                    }
                }
            }
            shuffle($notSelectedNotLastYearIds);
            shuffle($notSelectedLastYearIds);

            $finalShuffledIds = array_merge(
                $finalShuffledIds,
                $selectedIds,
                $notSelectedNotLastYearIds,
                $notSelectedLastYearIds
            );
        }
        return $finalShuffledIds;
    }


}