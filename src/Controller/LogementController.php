<?php

namespace App\Controller;

use App\Entity\Disponibilite;
use App\Entity\Image;
use App\Entity\Logement;
use App\Entity\Reservation;
use App\Form\LogementType;
use App\Repository\LogementRepository;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity; // permet d'acceder à l'utilisateur connecté
use Symfony\Component\HttpFoundation\JsonResponse;
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

    #[Route('/all', name: 'app_logement_all', methods: ['GET'])]
    public function all(LogementRepository $logementRepository): Response
    {
        $logements = $logementRepository->findAll();

        return $this->render('logement/all.html.twig', [
            'logements' => $logements,
        ]);
    }

    #[Route('/', name: 'app_logement_index', methods: ['GET'])]
    public function index(LogementRepository $logementRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $logements = $logementRepository->findBy(['user' => $user]); //récupère uniquement les logements de l'user connecté
        return $this->render('logement/index.html.twig', [
            'logements' => $logements, //logements de l'user
        ]);
    }

    #[Route('/new', name: 'app_logement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, PictureService $pictureService): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        $logement = new Logement();
        $logement->addDisponibilite(new Disponibilite());
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->security->getUser();
            // on récupère les images
            $images = $form->get('images')->getData();

            if (!empty($images)) {
                foreach ($images as $image) {
                    $folder = 'logement';
                    $fichier = $pictureService->add($image, $folder, 300, 300);

                    $img = new Image();
                    $img->setFilename($fichier);
                    $img->setLogements($logement);
                    $logement->addImage($img);
                }

                $logement->setUser($user);
                $entityManager->persist($logement);
                $entityManager->flush();

                return $this->redirectToRoute('app_logement_index', [], Response::HTTP_SEE_OTHER);
            }
        }


        return $this->render('logement/new.html.twig', [
            'logement' => $logement,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/{id}', name: 'app_logement_show', methods: ['GET'])]
    public function show(Logement $logement): Response
    {
        if (!$logement) {
            throw $this->createNotFoundException('Logement non trouvé');
        }

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
    public function edit(Request $request, Logement $logement, EntityManagerInterface $entityManager, PictureService $pictureService): Response
    {
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $images = $form->get('images')->getData();

            if (!empty($images)) {
                foreach ($images as $image) {
                    $folder = 'logement';
                    // on appel le service d'un ajout
                    $fichier = $pictureService->add($image, $folder, 300, 300);

                    $img = new Image();
                    $img->setFilename($fichier);  // Défini le nom de fichier
                    $img->setLogements($logement);
                    $logement->addImage($img);
                }
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_logement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logement/edit.html.twig', [
            'form' => $form->createView(),
            'logement' => $logement,
        ]);
    }
    #[Route('/{id}/delete-image', name: 'app_logement_delete_image', methods: ['POST'])]
    public function deleteImage(Request $request, Image $image, EntityManagerInterface $entityManager, PictureService $pictureService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($this->isCsrfTokenValid('delete' . $image->getId(), $data['_token'])) {
            $nom = $image->getFilename();

            if ($pictureService->delete($nom, 'logement', 300, 300)) {
                $entityManager->remove($image);
                $entityManager->flush();

                return new JsonResponse(['success' => true]);
            }
            return new JsonResponse(['error' => 'Erreur de suppression'], 400);
        }

        return new JsonResponse(['error' => 'Token invalide'], 400);
    }
}
