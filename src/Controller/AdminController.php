<?php

namespace App\Controller;

use App\Entity\Home;
use App\Entity\User;
use App\Entity\HomePeriod;
use App\Entity\Notification;
use App\Entity\Reservation;
use App\Entity\HomeImage;
use App\Form\HomeType;
use App\Form\HomePeriodType;
use App\Repository\HomeRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Repository\HomePeriodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;




#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    
    #[Route('/users', name: 'admin_users', methods: ['GET'])]
    public function users(Request $request, UserRepository $userRepository,LoggerInterface $logger, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10; 
        $matricule = $request->query->get('matricule');

        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC');

        if ($matricule) {
            $queryBuilder->where('u.matricule = :matricule')
                ->setParameter('matricule', $matricule);
        }

        $query = $queryBuilder
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery();

        $paginator = new Paginator($query, true);

        return $this->render('admin/users.html.twig', [
            'users' => $paginator,
            'currentPage' => $page,
            'totalPages' => ceil(count($paginator) / $pageSize),
        ]);
    } 

    #[Route('/users/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function newUser(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm('App\Form\UserType', $user, [
            'is_admin' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('cin')->getData()));

            $user->setRoles(['ROLE_USER']);
            // Check if the user already exists

            $user->setSit($form->get('sit')->getData()[0] ?? null);

            $existingUser = $userRepository->findOneBy(['matricule' => $user->getMatricule()]);
            if ($existingUser) {
                $this->addFlash('error', 'Un adhérent avec ce matricule existe déjà.');
                return $this->redirectToRoute('admin_user_new');
            }
            // Check if the user already exists by CIN
            $existingUserByCin = $userRepository->findOneBy(['cin' => $user->getCin()]);
            if ($existingUserByCin) {
                $this->addFlash('error', 'Un adhérent avec ce CIN existe déjà.');
                return $this->redirectToRoute('admin_user_new');
            }
            // If the user has an image, handle the file upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('users_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image');
                    return $this->redirectToRoute('admin_user_new');
                }

                $user->setImage($newFilename);
            }
            // If the user has no image, set it to null
            else {
                $user->setImage(null);
            }

            // Save the user
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'adhérent a été créé avec succès');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            $this->addFlash('danger','adhérent non trouvé');
            return $this->redirectToRoute('admin_users');
        }
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Delete user image if exists
            if ($user->getImage()) {
                $imagePath = $this->getParameter('users_images_directory').'/'.$user->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            } 

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'adhérent a été supprimé avec succès');
        }


        return $this->redirectToRoute('admin_users');
    }
    



        #[Route('/reservations', name: 'admin_reservations', methods: ['GET'])]
    public function Reservations(Request $request, ReservationRepository $reservationRepository, HomeRepository $homeRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10;

        $filters = [
            'region' => $request->query->get('region'),
            'residence' => $request->query->get('residence'),
            'nombreChambres' => $request->query->get('nombreChambres'),
        ];
        $filters = array_filter($filters);
        
        $session = $request->getSession();
        $shuffledIds = $session->get('shuffled_reservation_ids');

        if ($shuffledIds) {
            $allReservations = $reservationRepository->findByFilters($filters);
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
            $totalPages = ceil($totalReservations / $pageSize);

        } else {
            $paginator = $reservationRepository->findPaginatedByFilters($filters, $page, $pageSize);
            $reservations = iterator_to_array($paginator);
            $totalReservations = count($paginator); 
            $totalPages = ceil($totalReservations / $pageSize);
        }

        return $this->render('admin/reservations.html.twig', [
            'reservations' => $reservations,
            'allResidences' => $homeRepository->findAllResidences($filters),
            'allRegions' => $homeRepository->findAllRegions($filters),
            'allNbChambres' => $homeRepository->findAllNbChambres($filters),
            'residence' => $filters['residence'] ?? null,
            'region' => $filters['region'] ?? null,
            'nombreChambres' => $filters['nombreChambres'] ?? null,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/reservations/random-selection', name: 'admin_random_selection', methods: ['GET'])]
    public function randomSelection(
        Request $request,
        HomePeriodRepository $homePeriodRepository,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $filters = [
            'residence' => $request->query->get('residence'),
            'region' => $request->query->get('region'),
            'nombreChambres' => $request->query->get('nombreChambres'),
        ];

        if (empty($filters['residence']) || empty($filters['region']) || empty($filters['nombreChambres'])) {
            $this->addFlash('danger', 'Veuillez sélectionner une résidence, une région et un nombre de chambres.');
            return $this->redirectToRoute('admin_reservations', $filters);
        }

        $homePeriods = $homePeriodRepository->findByFilters($filters);
        if (empty($homePeriods)) {
            $this->addFlash('info', 'Aucune période de réservation trouvée pour les filtres sélectionnés.');
            return $this->redirectToRoute('admin_reservations', $filters);
        }

        $allReservationsForPeriods = $reservationRepository->findBy(['homePeriod' => $homePeriods]);

        $reservationsByPeriod = [];
        foreach ($allReservationsForPeriods as $reservation) {
            $reservationsByPeriod[$reservation->getHomePeriod()->getId()][] = $reservation;
        }
        
        $now = new \DateTime();

        foreach ($homePeriods as $period) {
            $maxUsers = $period->getHome()->getMaxUsers();
            if ($maxUsers < 1) {
                continue;
            }

            $periodReservations = $reservationsByPeriod[$period->getId()] ?? [];

            $alreadySelected = array_filter($periodReservations, fn($r) => $r->isSelected());
            $notSelected = array_filter($periodReservations, fn($r) => !$r->isSelected());
            
            $poolNotLastYear = array_filter($notSelected, fn($r) => !$r->getUser()->isLastYear());
            $poolLastYear = array_filter($notSelected, fn($r) => $r->getUser()->isLastYear());

            shuffle($poolNotLastYear);
            shuffle($poolLastYear);

            $spotsToFill = $maxUsers - count($alreadySelected);
            if ($spotsToFill <= 0) {
                continue;
            }

            $newlySelected = array_slice($poolNotLastYear, 0, $spotsToFill);
            $spotsRemaining = $spotsToFill - count($newlySelected);

            if ($spotsRemaining > 0) {
                $newlySelectedFromLastYear = array_slice($poolLastYear, 0, $spotsRemaining);
                $newlySelected = array_merge($newlySelected, $newlySelectedFromLastYear);
            }

            foreach ($newlySelected as $reservationToSelect) {
                $reservationToSelect->setIsSelected(true);
                $reservationToSelect->setDateSelection($now);

                $notification = new Notification();
                $notification->setMessage('Votre réservation a été sélectionnée et confirmée !');
                $notification->setCreatedAt($now);
                $notification->setType('user');
                $notification->setUser($reservationToSelect->getUser());
                
                $entityManager->persist($notification);
            }
        }

        $allReservationsAfterSelection = $reservationRepository->findByFilters($filters);
        $finalShuffledIds = $this->getFinalShuffledIds($allReservationsAfterSelection);
        $request->getSession()->set('shuffled_reservation_ids', $finalShuffledIds);

        $entityManager->flush();
        

        $this->addFlash('success', 'Sélection aléatoire effectuée avec succès.');

        return $this->redirectToRoute('admin_reservations', $filters);
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

    #[Route('/{id}/select', name: 'app_reservation_select', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function select(int $id,Request $request, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository,LoggerInterface $logger): Response
    {

        $logger->info('AdminController: select method called', ['id' => $id]);
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
            ]);
        }
        if ($reservation->isSelected()) {
            $this->addFlash('danger','Cette réservation a déjà été sélectionnée.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
            ]);
        }
        if ($reservation->isConfirmed()) {
            $this->addFlash('danger','Cette réservation est déjà confirmée.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
            ]);
        }
        if ($reservation->getHomePeriod()->getHome()->getMaxUsers() < count($reservation->getHomePeriod()->getReservations())) {
            $this->addFlash('danger','Cette période de réservation est déjà complète.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
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

        $this->addFlash('success', 'Le réservation a été sélectionnée.');
        return $this->redirectToRoute('admin_reservations', [
            'residence' => $residence,
            'region' => $region,
            'nombreChambres' => $nombreChambres,
        ]);
    }

    #[Route('/{id}/confirm', name: 'app_reservation_confirm', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function confirm(int $id,Request $request, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository): Response
    {
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
            ]);
        }
        if (!$reservation->isSelected()) {
            $this->addFlash('danger',"Cette réservation n'est pas sélectionnée.");
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
            ]);
        }
        if ($reservation->isConfirmed()) {
            $this->addFlash('danger','Cette réservation est déjà confirmée.');
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
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

        $this->addFlash('success', 'Le réservation a été confirmée.');
        return $this->redirectToRoute('admin_reservations', [
            'residence' => $residence,
            'region' => $region,
            'nombreChambres' => $nombreChambres,
        ]);
    }

    #[Route('/{id}/reject', name: 'app_reservation_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(
        int $id, 
        EntityManagerInterface $entityManager, 
        ReservationRepository $reservationRepository,
        Request $request
    ): Response {
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
            ]);

        }

        if (!$reservation->isSelected()) {
            $this->addFlash('danger',"Cette réservation n'est pas sélectionée.");
            return $this->redirectToRoute('admin_reservations', [
                'residence' => $residence,
                'region' => $region,
                'nombreChambres' => $nombreChambres,
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

        $this->addFlash('success', 'La réservation a été rejetée.');
        return $this->redirectToRoute('admin_reservations',[
            'residence' => $residence,
            'region' => $region,
            'nombreChambres' => $nombreChambres,
        ]);
    }




    #[Route('/homes', name: 'admin_homes', methods: ['GET'])]
    public function homes(Request $request, HomeRepository $homeRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10;

        $residence = $request->query->get('residence');
        $region = $request->query->get('region');
        $nombreChambres = $request->query->get('nombreChambres');
        
        $queryBuilder = $homeRepository->createQueryBuilder('h')
            ->orderBy('h.nom', 'ASC');

        $filters = [];
        if ($region) {
            $filters['region'] = $region;
            $queryBuilder->where("h.region LIKE :region")
                ->setParameter('region', '%'. $region .'%');
        }
        if ($residence) {
            $filters['residence'] = $residence;
            $queryBuilder->andWhere("h.residence LIKE :residence")
                ->setParameter('residence', '%'. $residence .'%');
        }
        if ($nombreChambres) {
            $filters['nombreChambres'] = $nombreChambres;
            $queryBuilder->andWhere("h.nombreChambres LIKE :nombreChambres")
                ->setParameter('nombreChambres', '%'. $nombreChambres .'%');
        }
        
        $query = $queryBuilder->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery();

        $homes = new Paginator($query,true);

        $allRegions = $homeRepository->findAllRegions($filters);
        $allResidences = $homeRepository->findAllResidences($filters);
        $allNbChambres = $homeRepository->findAllNbChambres($filters);
        
        
        return $this->render('admin/homes.html.twig', [
            'homes' => $homes,
            'allResidences' => $allResidences,
            'allRegions' => $allRegions,
            'allNbChambres' => $allNbChambres,
            'residence' => $residence,
            'region' => $region,
            'nombreChambres' => $nombreChambres,
            'currentPage' => $page,
            'totalPages' => ceil(count($homes) / $pageSize),
        ]);
    }
    
    #[Route('/home/new', name: 'admin_home_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, LoggerInterface $logger): Response
    {
        $home = new Home();
        $form = $this->createForm(HomeType::class, $home);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            
            // Set the home name first
            $home->setNom($form->get('residence')->getData(). ' - S+' . $form->get('nombreChambres')->getData());

            // Persist the home entity first
            $entityManager->persist($home);
            $entityManager->flush();

            // Then handle images
            $imageFiles = $form->get('imageFiles')->getData();
            if ($imageFiles) {
                foreach ($imageFiles as $imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('homes_images_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors du téléchargement d\'une image');
                        continue;
                    }

                    $homeImage = new HomeImage();
                    $homeImage->setFilename($newFilename);
                    $homeImage->setHome($home);
                    $entityManager->persist($homeImage);
                }
                $entityManager->flush();
            
            }
            $this->addFlash('success', 'La maison a été créée avec succès');
            return $this->redirectToRoute('admin_homes');
            
        }

        return $this->render('admin/home_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/home/{id}/edit', name: 'admin_home_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, HomeRepository $homeRepository, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $home = $homeRepository->find($id);
        if (!$home) {
            $this->addFlash('danger','Maison non trouvée');
            return $this->redirectToRoute('admin_homes');
        }
        $form = $this->createForm(HomeType::class, $home);
        $form->handleRequest($request);

        if ($form->isSubmitted()){
            $home->setNom($form->get('residence')->getData(). ' - S+' . $form->get('nombreChambres')->getData());
            
            if($form->isValid()) {
                if (count($reservationRepository->findActiveReservationByHome($home)) > $form->get('maxUsers')->getData()) {
                    $this->addFlash('danger', 'Cette maison contient des réservations sélectionnées. Vous ne pouvez pas décrémenter le nombre de maisons.');
                    return $this->redirectToRoute('app_home_show', ['id' => $home->getId()]);
                }
                // Then handle the images
                $imageFiles = $form->get('imageFiles')->getData();
                if ($imageFiles) {
                    foreach ($imageFiles as $imageFile) {
                        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
    
                        try {
                            $imageFile->move(
                                $this->getParameter('homes_images_directory'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                            $this->addFlash('error', 'Erreur lors du téléchargement d\'une image');
                            continue;
                        }
    
                        // Check if the image already exists in the home
                        $existingImage = $home->getImages()->filter(function (HomeImage $image) use ($newFilename) {
                            return $image->getFilename() === $newFilename;
                        })->first();
                        if ($existingImage) {
                            // If the image already exists, skip adding it again
                            continue;
                        }
                        $homeImage = new HomeImage();
                        $homeImage->setFilename($newFilename);
                        $homeImage->setHome($home);
                        $home->addImage($homeImage);
                        $entityManager->persist($homeImage);
                    }
                }
                $entityManager->persist($home);
                $entityManager->flush();
            
    
                $this->addFlash('success', 'La maison a été modifiée avec succès');
                return $this->redirectToRoute('admin_homes');
            }
            else {

                $this->addFlash('error', 'Form invalide. Veuillez vérifier les données saisies.');
            }
        }
        

        return $this->render('admin/home_edit.html.twig', [
            'home' => $home,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/home/{id}/delete', name: 'admin_home_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, HomeRepository $homeRepository, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $home = $homeRepository->findOneWithRelations($id);
        if (!$home) {
            $this->addFlash('danger','Maison non trouvée');
            return $this->redirectToRoute('admin_homes');
        }

        if ($this->isCsrfTokenValid('delete'.$home->getId(), $request->request->get('_token'))) {
            try {
                // Begin transaction
                $entityManager->beginTransaction();

                foreach ($home->getImages() as $image) {
                    // Delete physical file
                    $imagePath = $this->getParameter('homes_images_directory').'/'.$image->getFilename();
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    
                }
                $entityManager->flush();

                // 4. Finally remove the home
                $entityManager->remove($home);
                $entityManager->flush();

                // Commit transaction
                $entityManager->commit();

                $this->addFlash('success', 'La maison a été supprimée avec succès');
            } catch (\Exception $e) {
                $entityManager->rollback();
                $logger->error('Error in deletion process', [
                    'id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->addFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_homes');
    }

    #[Route('/home/image/{id}/delete', name: 'admin_home_image_delete', methods: ['POST'])]
    public function deleteImage(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $image = $entityManager->getRepository(HomeImage::class)->find($id);
        if (!$image) {
            $this->addFlash('danger','Image non trouvée');
            return $this->redirectToRoute('admin_homes');
        }

        $homeId = $image->getHome()->getId();

        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->request->get('_token'))) {
            // Delete physical file
            $imagePath = $this->getParameter('homes_images_directory').'/'.$image->getFilename();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }

            // Remove from database
            $entityManager->remove($image);
            $entityManager->flush();

            $this->addFlash('success', 'L\'image a été supprimée avec succès');
        }

        return $this->redirectToRoute('admin_home_edit', ['id' => $homeId]);
    }

    #[Route('/period', name: 'admin_add_all_periods')]
    public function addAllPeriods(Request $request, EntityManagerInterface $entityManager, HomeRepository $homeRepository): Response
    {
        $homes = $homeRepository->findAll();
        if (!$homes) {
            $this->addFlash('danger','Aucune maison trouvée');
            return $this->redirectToRoute('admin_homes');
        }
        
        $form = $this->createForm(HomePeriodType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($homes as $home) {
                $homePeriod = new HomePeriod();
                $homePeriod->setHome($home);
                $homePeriod->setDateDebut($form->get('dateDebut')->getData());
                $homePeriod->setDateFin($form->get('dateFin')->getData());
                
                // Check if the period overlaps with existing periods
                foreach ($home->getHomePeriods() as $period) {
                    if ($homePeriod->getDateDebut() < $period->getDateFin() && $homePeriod->getDateFin() > $period->getDateDebut()) {
                        $this->addFlash('danger', 'La période saisie chevauche une période existante pour la maison '.$home->getNom().'.');
                        return $this->redirectToRoute('admin_homes');
                    }
                }
                
                $entityManager->persist($homePeriod);
            }
            $entityManager->flush();

            $this->addFlash('success', 'Les périodes ont été ajoutées avec succès');
            return $this->redirectToRoute('admin_homes');
        }

        return $this->render('admin/homePeriod_new.html.twig', [
            'form' => $form->createView(),
            'homes' => $homes,
        ]);
    }

    #[Route('/period/{id}', name: 'admin_new_period')]
    public function newPeriod(int $id, Request $request, EntityManagerInterface $entityManager, HomeRepository $homeRepository) : Response {
        $home = $homeRepository->find($id);
        if (!$home) {
            $this->addFlash('danger','Maison non trouvée');
            return $this->redirectToRoute('admin_homes');
        }

        $homePeriod = new HomePeriod();
        $form = $this->createForm(HomePeriodType::class, $homePeriod);
        $homePeriod->setDateFin();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if the period overlaps with existing periods
            $existingPeriods = $home->getHomePeriods();
            foreach ($existingPeriods as $period) {
                if ($homePeriod->getDateDebut() < $period->getDateFin() && $homePeriod->getDateFin() > $period->getDateDebut()) {
                    $this->addFlash('danger', 'La période saisie chevauche une période existante.');
                    return $this->redirectToRoute('app_home_show', ['id' => $home->getId()]);
                }
            }
            $homePeriod -> setHome($home);
            $entityManager->persist($homePeriod);
            $entityManager->flush();

            $this->addFlash('success', 'La période a été ajoutée avec succès');
            return $this->redirectToRoute('app_home_show', ['id' => $home->getId()]);
        }

        return $this->render('admin/homePeriod_new.html.twig', [
            'home' => $home,
            'form' => $form->createView(),
        ]);
        
    }
    
    #[Route('/period/{id}/edit', name: 'admin_edit_period')]
    public function editPeriod(int $id, Request $request, EntityManagerInterface $entityManager,ReservationRepository $reservationRepository, HomePeriodRepository $homePeriodRepository, ): Response
    {
        $homePeriod = $homePeriodRepository->find($id);
        if (!$homePeriod) {
            $this->addFlash('danger','Période non trouvée');
            return $this->redirectToRoute('admin_homes');
        }

        $form = $this->createForm(HomePeriodType::class, $homePeriod);
        $form->handleRequest($request);
        
        
        
        $reservations = $homePeriod->getReservations();

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if the period overlaps with existing periods
            $existingPeriods = $home->getHomePeriods();
            foreach ($existingPeriods as $period) {
                if ($homePeriod->getDateDebut() < $period->getDateFin() && $homePeriod->getDateFin() > $period->getDateDebut()) {
                    $this->addFlash('danger', 'La période saisie chevauche une période existante.');
                    return $this->redirectToRoute('app_home_show', ['id' => $home->getId()]);
                }
            }
            
            // If there are reservations, notify users
            foreach ($reservations as $reservation) {
                $user = $reservation->getUser();
                if ($user) {
                    $notification = new Notification();
                    $notification->setMessage("La maison réservée a été modifiée.");
                    $notification->setCreatedAt(new \DateTime());
                    $notification->setType('user');
                    $notification->setUser($user);
                    
                    $entityManager->persist($notification);
                }
            }
            $entityManager->flush();

            $this->addFlash('success', 'La période a été modifiée avec succès');
            return $this->redirectToRoute('app_home_show', ['id' => $homePeriod->getHome()->getId()]);
        }

        return $this->render('admin/homePeriod_edit.html.twig', [
            'form' => $form->createView(),
            'homePeriod' => $homePeriod,
        ]);
    }

    #[Route('/period/{id}/delete', name: 'admin_delete_period', methods: ['POST'])]
    public function deletePeriod(Request $request, int $id, HomePeriodRepository $homePeriodRepository, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $homePeriod = $homePeriodRepository->findOneById($id);
        if (!$homePeriod) {
            $this->addFlash('danger','Période non trouvée');
            return $this->redirectToRoute('admin_homes');
        }

        // Store the home ID before deletion
        $homeId = $homePeriod->getHome() ? $homePeriod->getHome()->getId() : null;

        if ($this->isCsrfTokenValid('delete'.$homePeriod->getId(), $request->request->get('_token'))) {
            try {
                // Get reservations before removing the period
                $reservations = $homePeriod->getReservations()->toArray();
                
                $entityManager->remove($homePeriod);
                $entityManager->flush();

                // Create notifications after successful deletion
                foreach ($reservations as $reservation) {
                    $user = $reservation->getUser();
                    if ($user) {
                        $notification = new Notification();
                        $notification->setMessage("La maison réservée n'est plus disponible.");
                        $notification->setCreatedAt(new \DateTime());
                        $notification->setType('user');
                        $notification->setUser($user);
                        
                        $entityManager->persist($notification);
                    }
                }
                $entityManager->flush();
                $this->addFlash('success', 'La période a été supprimée avec succès');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression de la période: ' . $e->getMessage());
            }
        }

        if ($homeId) {
            return $this->redirectToRoute('app_home_show', ['id' => $homeId]);
        }
        return $this->redirectToRoute('admin_homes');
    }


    

} 