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
            return $this->redirectToRoute('app_login');
        }
        $reservations = $reservationRepository->findBy(['user' => $user]);
        $logements = [];
        foreach ($reservations as $reservation) {
            $logements[] = $reservation->getLogement(); // Récupère le logement associé
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations, //logements de l'user

        ]);
    }



    #[Route('/new/{logementId}', name: 'app_reservation_new')]
    public function newId(Request $request, EntityManagerInterface $entityManager, $logementId): Response
    {
        // redirige vers la page de connexion si pas d'utilisateur connecté
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        $logement = $this->entityManager->getRepository(Logement::class)->find($logementId);

        // Obtien les réservations existantes pour ce logement
        $reservations = $this->entityManager->getRepository(Reservation::class)->findBy(['logement' => $logement]);

        // Filtre les dates de réservation pour éviter le chevauchement
        $reservedDates = [];
        foreach ($reservations as $reservation) {
            $reservedDates[] = [
                'debut' => $reservation->getDateDebut(),
                'fin' => $reservation->getDateFin(),
            ];
        }

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        $errorMessage = null;
        $totalPrice = 0;


        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifie si les dates proposé ne chevauche pas les réservations existantes
            $dateDebut = $reservation->getDateDebut();
            $dateFin = $reservation->getDateFin();

            foreach ($reservedDates as $reservedDate) {
                if (
                    $dateDebut <= $reservedDate['fin'] &&
                    $dateFin >= $reservedDate['debut']
                ) {
                    $errorMessage = "Les dates choisies sont déjà réservées.";
                }
            }
            if (!$errorMessage) {
                $totalPrice = $this->calculateTotalPrice($reservation, $logement);
                $reservation->setLogement($logement);
                $reservation->setUser($this->security->getUser());
                $entityManager->persist($reservation);
                $entityManager->flush();

                return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
            }
        }
        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
            'logement' => $logement,
            'error_message' => $errorMessage,
            'total_price' => $totalPrice,

        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        $totalPrice = $this->calculateTotalPrice($reservation, $reservation->getLogement());

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
            'logement' => $reservation->getLogement(), //logement associé  
            'total_price' => $totalPrice,

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

    public function calculateTotalPrice(Reservation $reservation, Logement $logement): float
    {
        $dateDebut = $reservation->getDateDebut();
        $dateFin = $reservation->getDateFin();

        $totalPrice = 0;

        foreach ($logement->getDisponibilites() as $disponibilité) {
            if (
                $disponibilité->getDateDebut() <= $dateFin &&
                $disponibilité->getDateFin() >= $dateDebut
            ) {
                $overlapStart = max($dateDebut, $disponibilité->getDateDebut());
                $overlapEnd = min($dateFin, $disponibilité->getDateFin());

                $numberOfDays = $overlapEnd->diff($overlapStart)->days + 1;
                $totalPrice += $numberOfDays * $disponibilité->getPrix();
            }
        }

        return $totalPrice;
    }
}
