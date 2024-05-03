<?php

namespace App\Controller;


use App\Entity\Logement;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, SecurityBundleSecurity $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }


    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {

        $user = $this->security->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette page.");
        }
        $reservations = $reservationRepository->findBy(['user' => $user]);
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations, //logements de l'user
        ]);
    }



    #[Route('/new/{logementId}', name: 'app_reservation_new')]
    public function newId(Request $request, EntityManagerInterface $entityManager, $logementId): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {  // redirige vers la page de connexion si pas d'utilisateur connecté
            return $this->redirectToRoute('app_login');
        }

        $user = $this->security->getUser(); // Obtenir l'utilisateur connecté
        if (!$user) {
            throw $this->createAccessDeniedException("Utilisateur non trouvé.");
        }

        $logement = $entityManager->getRepository(Logement::class)->find($logementId);
        if (!$logement) {
            throw $this->createNotFoundException("Logement introuvable.");
        }

        $reservation = new Reservation();
        $reservation->setLogement($logement); // le logement est associé a la réservation
        $reservation->setUser($user); // l'user connecté est associé a la reservation
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_show', [
                'id' => $reservation->getId(),

                // ID de la réservation créée
            ]);
        }

        return $this->render('reservation/new.html.twig', [
            'form' => $form,
            'logement' => $logement,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
            'logement' => $reservation->getLogement() //logement associé    

        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reservation->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
}
