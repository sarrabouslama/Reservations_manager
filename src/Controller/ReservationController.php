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

#[Route('/reservations')]
#[IsGranted('ROLE_User','ROLE_ADMIN')]
class ReservationController extends AbstractController
{
    #[Route('/user/reservation/{id}', name: 'app_user_reservation')]
    #[IsGranted('ROLE_USER')]
    public function reservation(int $id, ReservationRepository $reservationRepository, UserRepository $userRepository): Response
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
        $reservations = $reservationRepository->findBy(['user' => $user]);
        return $this->render('user/reservation.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/new/{id}', name: 'app_reservation_new', methods: ['POST','GET'])]
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
        if (!$user->getTel() || !$user->getEmail()) {
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

        $dateDebut = $dateDebutStr ? new \DateTime($dateDebutStr) : null;
        $dateFin = $dateFinStr ? new \DateTime($dateFinStr) : null;

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
            $this->addFlash('error', 'Aucune période disponible pour ces dates.');
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
    public function uploadReceipt(int $id, Request $request, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager): Response
    {
        $reservation = $reservationRepository->find($id);
        if (!$reservation || $reservation->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Réservation non trouvée ou accès refusé.');
        }
        if (!$reservation->isSelected()) {
            $this->addFlash('error', 'Vous ne pouvez importer un reçu que pour une réservation sélectionnée.');
            return $this->redirectToRoute('user_reservations');
        }
        $file = $request->files->get('receipt');
        if ($file) {
            $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                $this->addFlash('error', 'Format de fichier non supporté.');
                return $this->redirectToRoute('user_reservations');
            }
            $filename = 'receipt_' . $reservation->getId() . '_' . uniqid() . '.' . $file->guessExtension();
            $file->move($this->getParameter('receipts_directory'), $filename);
            $reservation->setReceiptFilename($filename);
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Reçu importé avec succès.');
        } else {
            $this->addFlash('error', 'Aucun fichier reçu.');
        }
        return $this->redirectToRoute('app_user_reservation', ['id' => $this->getUser()->getId()]);
    }
}