<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ReservationRepository;
use App\Entity\Payement;
use App\Entity\Reservation;
use App\Form\PayementType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PayementRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Repository\PiscineReservationRepository;
use App\Repository\UserRepository;



#[IsGranted('ROLE_ADMIN')]
class PayementController extends AbstractController
{
    #[Route('/payement/{id}', name: 'app_new_payement')]
    public function new(int $id, ReservationRepository $reservationRepository,EntityManagerInterface $entityManager, Request $request): Response
    {
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation non trouvée');
        }
        if (!$reservation->isSelected()) {
            throw $this->createAccessDeniedException('La réservation n\'est pas sélectionnée');
        }
        if ($reservation->getPayement()) {
            throw $this->createAccessDeniedException('Formulaire déjà rempli');
        }
        $payement = new Payement();
        $payement->setDateSaisie(new \DateTime());
        $payement->setReservation($reservation);
        $payement->setMontantGlobal($reservation->getHomePeriod()->getHome()->getPrix());
        $payement->setCodeOpposition('1041');
        $payement->setDateDebut(new \DateTime('2025-09-01'));
        $payement->setNbMois(6);
        $form = $this->createForm(PayementType::class, $payement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $payement->setMontantGlobal($form->get('montantGlobal')->getData());
            $payement->setAvance($form->get('avance')->getData());
            $payement->setNbMois($form->get('nbMois')->getData());
            $payement->setModeEcheance($form->get('modeEcheance')->getData());
            $payement->setCodeOpposition($form->get('codeOpposition')->getData());
            $payement->setDateDebut($form->get('dateDebut')->getData());
            $entityManager->persist($payement);
            $reservation->setPayement($payement);
            $reservation->setIsConfirmed(true);
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Le opposition a été créé avec succès.');
            return $this->redirectToRoute('app_user_reservation', ['id' => $reservation->getUser()->getId()]);
        }

        return $this->render('payement/new_payement.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
        
    }

    #[Route('/payement/{id}/edit', name: 'app_edit_payement')]
    public function edit(int $id, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation non trouvée');    
        }
        if (!$reservation->getPayement()) {
            throw $this->createAccessDeniedException('Aucune opposition trouvée pour cette réservation');
        }
        $payement = $reservation->getPayement();
        $dateSaisie = $payement->getDateSaisie();
        $date = (new \DateTime())->setTime(0, 0, 0);
        $form = $this->createForm(PayementType::class, $payement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $payement->setMontantGlobal($form->get('montantGlobal')->getData());
            $payement->setAvance($form->get('avance')->getData());
            $payement->setNbMois($form->get('nbMois')->getData());
            $payement->setModeEcheance($form->get('modeEcheance')->getData());
            $payement->setCodeOpposition($form->get('codeOpposition')->getData());
            $payement->setDateDebut($form->get('dateDebut')->getData());
            $entityManager->persist($payement);
            $entityManager->flush();
            $this->addFlash('success', 'L\'opposition a été modifié avec succès.');
            return $this->redirectToRoute('app_user_reservation', ['id' => $reservation->getUser()->getId()]);
        }
        
        return $this->render('payement/edit_payement.html.twig', [
            'date' => $date,
            'dateSaisie' => $dateSaisie,
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
    }

    #[Route('/payement/{id}/delete', name: 'app_delete_payement')]
    public function delete(int $id, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager): Response
    {
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation non trouvée');
        }
        if (!$reservation->getPayement()) {
            throw $this->createAccessDeniedException('Aucun opposition trouvée pour cette réservation');
        }
        $payement = $reservation->getPayement();
        $reservation->setIsConfirmed(false);
        $reservation->deletePayement();
        $entityManager->persist($reservation);
        $entityManager->remove($payement);
        $entityManager->flush();
        $this->addFlash('success', 'L\'opposition a été supprimé avec succès.');
        return $this->redirectToRoute('app_user_reservation', ['id' => $reservation->getUser()->getId()]);
    }

    #[Route('/payements/show', name: 'app_show_payements')]
    public function showMaisons(Request $request, EntityManagerInterface $entityManager, PayementRepository $payementRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10; // Nombre de paiements par page
        $matricule = $request->query->get('matricule');

        $queryBuilder = $payementRepository->createQueryBuilder('p')
            ->join('p.reservation', 'r')
            ->addSelect('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u');

        if ($matricule) {
            $queryBuilder->andWhere('u.matricule = :matricule')
                         ->setParameter('matricule', $matricule);
        }
        $query =  $queryBuilder->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery();

        $payements = new Paginator($query, true);

        return $this->render('payement/index.html.twig', [
            'payements' => $payements,
            'currentPage' => $page,
            'totalPages' => ceil(count($payements) / $pageSize),
        ]);
    }

    #[Route('/piscine/payements/show', name: 'app_show_payements_piscine')]
    public function showPiscines(Request $request, EntityManagerInterface $entityManager, PayementRepository $payementRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = 10; // Nombre de paiements par page
        $matricule = $request->query->get('matricule');

        $queryBuilder = $payementRepository->createQueryBuilder('p')
            ->join('p.piscineReservation', 'r')
            ->addSelect('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u');

        if ($matricule) {
            $queryBuilder->andWhere('u.matricule = :matricule')
                         ->setParameter('matricule', $matricule);
        }
        $query =  $queryBuilder->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery();

        $piscines = new Paginator($query, true);

        return $this->render('payement/piscines.html.twig', [
            'piscines' => $piscines,
            'currentPage' => $page,
            'totalPages' => ceil(count($piscines) / $pageSize),
        ]);
    }

    #[Route('/payement/piscine/{id}', name: 'app_payement_piscine')]
    public function newPayementPiscine(int $id, PiscineReservationRepository $piscineReservationRepository,EntityManagerInterface $entityManager, Request $request): Response
    {
        $reservation = $piscineReservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation non trouvée');
        }
        if (!$reservation->isSelected()) {
            throw $this->createAccessDeniedException('La réservation n\'est pas sélectionnée');
        }
        if ($reservation->getPayement()) {
            throw $this->createAccessDeniedException('Formulaire déjà rempli');
        }
        $payement = new Payement();
        $payement->setDateSaisie(new \DateTime());
        $payement->setPiscineReservation($reservation);
        $payement->setMontantGlobal($reservation->getPiscine()->getPrixFinal());
        $payement->setAvance($reservation->getPiscine()->getAvance());
        $payement->setCodeOpposition('1041');
        $payement->setDateDebut(new \DateTime('2025-09-01'));
        $payement->setNbMois(6);
        $form = $this->createForm(PayementType::class, $payement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($payement);
            $reservation->setPayement($payement);
            $reservation->setConfirmed(true);
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Le opposition a été créé avec succès.');
            return $this->redirectToRoute('app_user_reservation', ['id' => $reservation->getUser()->getId()]);
        }

        return $this->render('payement/new_payement.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
        
    }

    #[Route('/payement/piscine/{id}/edit', name: 'app_edit_payement_piscine')]
    public function editPayementPiscine(int $id, PiscineReservationRepository $piscineReservationRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $reservation = $piscineReservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation non trouvée');
        }

        $payement = $reservation->getPayement();
        $dateSaisie = $payement->getDateSaisie();
        $date = (new \DateTime())->setTime(0, 0, 0);
        $form = $this->createForm(PayementType::class, $payement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le paiement a été mis à jour avec succès.');
            return $this->redirectToRoute('app_user_reservation', ['id' => $reservation->getUser()->getId()]);
        }

        return $this->render('payement/edit_payement.html.twig', [
            'date' => $date,
            'dateSaisie' => $dateSaisie,
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
    }

    
    #[Route('/piscine/payement/{id}/delete', name: 'app_delete_payement_piscine')]
    public function deletePiscine(int $id, PiscineReservationRepository $piscineReservationRepository, EntityManagerInterface $entityManager): Response
    {
        $reservation = $piscineReservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation non trouvée');
        }
        if (!$reservation->getPayement()) {
            throw $this->createAccessDeniedException('Aucun opposition trouvée pour cette réservation');
        }
        $payement = $reservation->getPayement();
        $reservation->setConfirmed(false);
        $reservation->setPayement(null);
        $entityManager->persist($reservation);
        $entityManager->remove($payement);
        $entityManager->flush();
        $this->addFlash('success', 'L\'opposition a été supprimé avec succès.');
        return $this->redirectToRoute('app_user_reservation', ['id' => $reservation->getUser()->getId()]);
    }
}
