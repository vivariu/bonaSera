<?php

namespace App\Controller;

use App\Repository\LogementRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class HomeController extends AbstractController
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, SecurityBundleSecurity $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }
    #[Route('/', name: 'app_home')]
    public function index(LogementRepository $logementRepository, ReservationRepository $reservationRepository): Response
    {

        $logements = $logementRepository->findAll();

        $reservations = $reservationRepository->findBy(['user' => $this->security->getUser()]);

        return $this->render('home/index.html.twig', [
            'logements' => $logements,
            'reservations' => $reservations,
        ]);
    }
}
