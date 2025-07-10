<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PiscineRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\PiscineReservation;
use App\Form\ContactInfoType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\PiscineReservationRepository;
use App\Form\PiscineType;
use App\Entity\Piscine;


class PiscineController extends AbstractController
{
    #[Route('/piscine', name: 'app_piscine')]
    public function index(PiscineRepository $piscineRepository, Request $request): Response
    {
        $region = $request->query->get('region');
        $hotel = $request->query->get('hotel');
        $filters = [];
        if ($region) {
            $filters['region'] = $region;
        }
        if ($hotel) {
            $filters['hotel'] = $hotel;
        }
        $allRegions = $piscineRepository->findAllRegions($filters);
        $allHotels = $piscineRepository->findAllHotels($filters);
        return $this->render('piscine/index.html.twig', [
            'piscines' => $piscineRepository->findBy($filters),
            'allRegions' => $allRegions,
            'allHotels' => $allHotels,
            'region' => $region,
            'hotel' => $hotel,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/piscine/new', name: 'admin_piscine_new')]
    public function newPiscine(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }

        $piscine = new Piscine();
        $form = $this->createForm(PiscineType::class, $piscine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $piscine->setPrixFinal($piscine->getPrixInitial() - $piscine->getAmicale());
            $entityManager->persist($piscine);
            $entityManager->flush();
            $this->addFlash('success', 'Piscine ajoutée avec succès.');
            return $this->redirectToRoute('app_piscine');
        }

        return $this->render('piscine/piscine_new.html.twig', [
            'form' => $form->createView(),
            'type' =>'ajouter'
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/piscine/edit/{id}', name: 'admin_piscine_edit')]
    public function editPiscine(int $id, PiscineRepository $piscineRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }

        $piscine = $piscineRepository->find($id);
        if (!$piscine) {
            throw $this->createNotFoundException('Piscine non trouvée');
        }

        $form = $this->createForm(PiscineType::class, $piscine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $piscine->setPrixFinal($piscine->getPrixInitial() - $piscine->getAmicale());
            $entityManager->persist($piscine);
            $entityManager->flush();
            $this->addFlash('success', 'Piscine modifiée avec succès.');
            return $this->redirectToRoute('app_piscine');

        }

        return $this->render('piscine/piscine_new.html.twig', [
            'form' => $form->createView(),
            'type' => 'modifier',
            'piscine' => $piscine,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/piscine/delete/{id}', name: 'admin_piscine_delete')]
    public function deletePiscine(int $id, PiscineRepository $piscineRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }

        $piscine = $piscineRepository->find($id);
        if (!$piscine) {
            throw $this->createNotFoundException('Piscine non trouvée');
        }

        $entityManager->remove($piscine);
        $entityManager->flush();
        $this->addFlash('success', 'Piscine supprimée avec succès.');
        return $this->redirectToRoute('app_piscine');
    }


    #[Route('/piscine/{id}', name: 'app_piscine_reserver')]
    public function reserverPiscine(int $id, PiscineRepository $piscineRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Les administrateurs ne peuvent pas faire de réservations.');
        }
        $piscine = $piscineRepository->find($id);
        if (!$piscine) {
            throw $this->createNotFoundException('Piscine not found');
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

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
                return $this->redirectToRoute('app_reserver_piscine', [
                    'id' => $id,
                ]);
            }

            return $this->render('reservation/contact_info.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        if ($user->getPiscineReservation()) {
            if ($user->getPiscineReservation()->getPiscine()->getId() == $id) {
                $this->addFlash('info', 'Vous avez déjà réservé cette piscine.');
                return $this->redirectToRoute('app_piscine');
            } else {
                $oldPiscine = $user->getPiscineReservation();
                if ($oldPiscine->isConfirmed()) {
                    $this->addFlash('danger', 'Vous avez déjà une réservation confirmée pour ' . $oldPiscine->getPiscine()->getHotel() . '.');
                    return $this->redirectToRoute('app_piscine');
                }
                $this->addFlash('warning', 'Votre ancienne réservation pour ' . $oldPiscine->getPiscine()->getHotel() . ' a été annulée.');
                $entityManager->remove($oldPiscine);
                $entityManager->flush();
            }
        }

        $piscineReservation = new PiscineReservation();
        $piscineReservation->setPiscine($piscine);
        $piscineReservation->setUser($user);
        $entityManager->persist($piscineReservation);
        $entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été enregistrée avec succès.');

        return $this->redirectToRoute('app_piscine');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/piscine/reservations', name: 'admin_piscine_reservations')]
    public function adminPiscineReservations(PiscineReservationRepository $piscineReservationRepository, Request $request, PiscineRepository $piscineRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10;

        $region = $request->query->get('region');
        $hotel = $request->query->get('hotel');
        $filters = [];
        if ($region) {
            $filters['region'] = $region;
        }
        if ($hotel) {
            $filters['hotel'] = $hotel;
        }
        $allRegions = $piscineRepository->findAllRegions($filters);
        $allHotels = $piscineRepository->findAllHotels($filters);

        $reservations = $piscineReservationRepository->findByFilters($filters, $page, $pageSize);
        return $this->render('admin/piscine_reservations.html.twig', [
            'reservations' => $reservations,
            'currentPage' => $page,
            'totalPages' => ceil(count($reservations) / $pageSize),
            'allRegions' => $allRegions,
            'allHotels' => $allHotels,
            'region' => $region,
            'hotel' => $hotel,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/piscine/reservation/select/{id}', name: 'admin_piscine_reservation_select')]
    public function selectPiscineReservation(int $id, PiscineReservationRepository $piscineReservationRepository, EntityManagerInterface $entityManager): Response
    {
        $reservation = $piscineReservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }
        if ($reservation->isSelected()) {
            $this->addFlash('info', 'Cette réservation est déjà sélectionnée.');
        } else {
            if ($reservation->getUser()->getReservation() && $reservation->getUser()->getReservation()->isSelected()) {
                $this->addFlash('danger', 'L\'adhérent a une réservation dans '. $reservation->getUser()->getReservation()->getHomePeriod()->getHome()->getNom() . '.');
                return $this->redirectToRoute('admin_piscine_reservations');
            }
            $reservation->setSelected(true);
            $reservation->setDateSelection(new \DateTime());
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation sélectionnée avec succès.');
        }

        return $this->redirectToRoute('admin_piscine_reservations');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/piscine/reservation/reject/{id}', name: 'admin_piscine_reservation_reject')]
    public function rejectPiscineReservation(int $id, PiscineReservationRepository $piscineReservationRepository, EntityManagerInterface $entityManager): Response
    {
        $reservation = $piscineReservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }
        if (!$reservation->isSelected()) {
            $this->addFlash('info', 'Cette réservation n\'est pas sélectionnée.');
        } else {
            $reservation->setSelected(false);
            $reservation->setConfirmed(false);
            $reservation->setPayement(null);
            $reservation->setDateSelection(null);
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation rejetée avec succès.');
        }

        return $this->redirectToRoute('admin_piscine_reservations');
    }

    #[Route('piscine/{id}/upload-receipt', name: 'app_upload_receipt_piscine', methods: ['POST'])]
    public function uploadReceipt(int $id, Request $request, PiscineReservationRepository $piscineReservationRepository, EntityManagerInterface $entityManager): Response
    {
        $reservation = $piscineReservationRepository->find($id);
        if (!$reservation || $reservation->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Réservation non trouvée ou accès refusé.');
        }
        if (!$reservation->isSelected()) {
            $this->addFlash('danger', 'Vous ne pouvez importer un reçu que pour une réservation sélectionnée.');
            return $this->redirectToRoute('app_user_reservation', ['id' => $this->getUser()->getId()]);
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

    #[Route('piscine/{id}/cancel', name: 'app_piscine_cancel', methods: ['POST'])]
    public function cancelPiscineReservation(int $id, PiscineReservationRepository $piscineReservationRepository, EntityManagerInterface $entityManager): Response
    {
        $reservation = $piscineReservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée.');
        }
        if ($reservation->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }
        if ($reservation->isConfirmed()) {
            $this->addFlash('danger', 'Vous ne pouvez pas annuler une réservation confirmée.');
            return $this->redirectToRoute('app_user_reservation', ['id' => $reservation->getUser()->getId()]);
        }
        $entityManager->remove($reservation);
        $entityManager->flush();
        $this->addFlash('success', 'Réservation annulée avec succès.');
        return $this->redirectToRoute('app_user_reservation', ['id' => $reservation->getUser()->getId()]);
    }
}