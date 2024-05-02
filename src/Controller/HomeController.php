<?php

namespace App\Controller;

use App\Repository\LogementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(LogementRepository $logementRepository): Response
    {

        $logements = $logementRepository->findAll();

        return $this->render('home/index.html.twig', [
            'logements' => $logements,

        ]);
    }
}
