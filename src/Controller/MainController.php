<?php

namespace App\Controller;

use App\Repository\HomeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\ContactInfoType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;


class MainController extends AbstractController
{

    #[Route('/', name: 'app_home_main')]
    public function main(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user){
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
            }
            
            return $this->render('reservation/contact_info.html.twig', [
                'form' => $form->createView(),
            ]);
        }
        return $this->render('main/index.html.twig');

    }

    #[Route('/hotels', name: 'app_hotels')]
    #[IsGranted('ROLE_USER', 'ROLE_ADMIN', 'ROLE_SEMIADMIN')]
    public function hotels(): Response
    {
        return $this->render('main/hotels.html.twig');
    }

    #[Route('/bloque', name: 'app_block')]
    #[IsGranted('ROLE_USER', 'ROLE_ADMIN', 'ROLE_SEMIADMIN')]
    public function block(): Response
    {
        return $this->render('main/block.html.twig');
    }

    #[Route('/bloque/hotels', name: 'app_block_hotels')]
    #[IsGranted('ROLE_USER', 'ROLE_ADMIN', 'ROLE_SEMIADMIN')]
    public function blockHotels(): Response
    {
        return $this->render('main/block_hotels.html.twig');
    }
} 