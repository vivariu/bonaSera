<?php

namespace App\Controller;

use App\Entity\Disponibilite;
use App\Entity\Logement;
use App\Entity\Reservation;
use App\Form\LogementType;
use App\Repository\LogementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity; // permet d'acceder à l'utilisateur connecté
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/logement')]
class LogementController extends AbstractController
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, SecurityBundleSecurity $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    #[Route('/', name: 'app_logement_index', methods: ['GET'])]
    public function index(LogementRepository $logementRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette page.");
        }
        $logements = $logementRepository->findBy(['user' => $user]); //récupère uniquement les logements de l'user connecté
        return $this->render('logement/index.html.twig', [
            'logements' => $logements, //logements de l'user
        ]);
    }

    #[Route('/new', name: 'app_logement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {  // redirige vers la page si pas connecté
            return $this->redirectToRoute('app_login');
        }

        $logement = new Logement();

        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->security->getUser();

            $logement->setUser($user);
            $entityManager->persist($logement);
            $entityManager->flush();

            return $this->redirectToRoute('app_logement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logement/new.html.twig', [
            'logement' => $logement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_logement_show', methods: ['GET'])]
    public function show(Logement $logement): Response
    {
        // Obtenir toutes les réservations associées à ce logement
        $reservations = $this->entityManager->getRepository(Reservation::class)->findBy(['logement' => $logement]);

        // Obtien les plages de dates réservées
        $reservedDates = [];
        foreach ($reservations as $reservation) {
            $reservedDates[] = [
                'debut' => $reservation->getDateDebut(),
                'fin' => $reservation->getDateFin(),
            ];
        }

        // Filtre les disponibilités qui ne se chevauchent pas avec les réservations
        $availableDisponibilites = [];
        foreach ($logement->getDisponibilites() as $disponibilite) {
            $isReserved = false;
            foreach ($reservedDates as $reservedDate) {
                if (
                    $disponibilite->getDateDebut() <= $reservedDate['fin'] &&
                    $disponibilite->getDateFin() >= $reservedDate['debut']
                ) {
                    $isReserved = true;
                    break;
                }
            }
            if (!$isReserved) {
                $availableDisponibilites[] = $disponibilite;
            }
        }

        return $this->render('logement/show.html.twig', [
            'logement' => $logement,
            'availableDisponibilites' => $availableDisponibilites,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_logement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logement/edit.html.twig', [
            'logement' => $logement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_logement_delete', methods: ['POST'])]
    public function delete(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $logement->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($logement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logement_index', [], Response::HTTP_SEE_OTHER);
    }
}
