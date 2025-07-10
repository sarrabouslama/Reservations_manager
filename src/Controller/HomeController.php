<?php

namespace App\Controller;

use App\Entity\Home;
use App\Repository\HomeRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\HomePeriodRepository;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;


#[Route('/homes')]
class HomeController extends AbstractController
{
    
    #[Route('', name: 'app_home_index')]
    public function index(Request $request, HomeRepository $homeRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }
        $residence = $request->query->get('residence');
        $region = $request->query->get('region');
        $nombreChambres = $request->query->get('nombreChambres');
        $page = max(1,$request->query->getInt('page', 1));

        $filters = [];
        if ($region) {
            $filters['region'] = $region;
        }
        if ($residence) {
            $filters['residence'] = $residence;
        }
        if ($nombreChambres) {
            $filters['nombreChambres'] = $nombreChambres;
        }

        $allRegions = $homeRepository->findAllRegions($filters);
        $allResidences = $homeRepository->findAllResidences($filters);
        $allNbChambres = $homeRepository->findAllNbChambres($filters);

        $homes = $homeRepository->findByFilters($filters, $page, 9);

        $totalHomes = count($homes);
        $totalPages = ceil($totalHomes / 9);
        return $this->render('home/index.html.twig', [
            'homes' => $homes,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'allResidences' => $allResidences,
            'allRegions' => $allRegions,
            'allNbChambres' => $allNbChambres,
            'residence' => $residence,
            'region' => $region,
            'nombreChambres' => $nombreChambres,
        ]);
    }

    #[Route('/detail/{id}', name: 'app_home_show')]
    public function show(int $id, ReservationRepository $reservationRepository, HomeRepository $homeRepository, LoggerInterface $logger): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }
        $home = $homeRepository->find($id);
        if (!$home) {
            throw $this->createNotFoundException('Home not found');
        }
        $logger->info('MAPS URL: ' . $home->getMapsUrl());

        return $this->render('home/show.html.twig', [
            'home' => $home,
            'date' => new \DateTime(),
        ]);
    }

    #[Route('/period/{id}/reservation-count', name: 'app_period_reservation_count', methods: ['GET'])]
    public function reservationCount(int $id, HomePeriodRepository $homePeriodRepository): JsonResponse
    {
        $period = $homePeriodRepository->find($id);
        if (!$period) {
            return new JsonResponse(['count' => 0], 404);
        }
        $count = count($period->getReservations());
        return new JsonResponse(['count' => $count]);
    }

    

} 