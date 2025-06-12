<?php

namespace App\Controller;

use App\Repository\HomeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{

    #[Route('/', name: 'app_home_main')]
    public function main(): Response
    {
        if ($this->getUser()) {
            return $this->render('main/index.html.twig');
        }
        return $this->redirectToRoute('app_login');
    }
} 