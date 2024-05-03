<?php

namespace App\Entity;

use App\Repository\LogementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogementRepository::class)]
class Logement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $nb_voyageur = null;

    #[ORM\Column(length: 255)]
    private ?string $nb_chambre = null;

    #[ORM\Column(length: 255)]
    private ?string $nb_salle_de_bain = null;

    #[ORM\ManyToOne(inversedBy: 'logements')]
    private ?User $user = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'logement')]
    private Collection $reservations;

    /**
     * @var Collection<int, Disponibilite>
     */
    #[ORM\OneToMany(targetEntity: Disponibilite::class, mappedBy: 'logement', cascade: ['persist'])]
    private Collection $disponibilites;


    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->disponibilites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getNbVoyageur(): ?string
    {
        return $this->nb_voyageur;
    }

    public function setNbVoyageur(string $nb_voyageur): static
    {
        $this->nb_voyageur = $nb_voyageur;

        return $this;
    }

    public function getNbChambre(): ?string
    {
        return $this->nb_chambre;
    }

    public function setNbChambre(string $nb_chambre): static
    {
        $this->nb_chambre = $nb_chambre;

        return $this;
    }

    public function getNbSalleDeBain(): ?string
    {
        return $this->nb_salle_de_bain;
    }

    public function setNbSalleDeBain(string $nb_salle_de_bain): static
    {
        $this->nb_salle_de_bain = $nb_salle_de_bain;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setLogement($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getLogement() === $this) {
                $reservation->setLogement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Disponibilite>
     */
    public function getDisponibilites(): Collection
    {
        return $this->disponibilites;
    }

    public function addDisponibilite(Disponibilite $disponibilite): static
    {
        if (!$this->disponibilites->contains($disponibilite)) {
            $this->disponibilites->add($disponibilite);
            $disponibilite->setLogement($this);
        }

        return $this;
    }

    public function removeDisponibilite(Disponibilite $disponibilite): static
    {
        if ($this->disponibilites->removeElement($disponibilite)) {
            // set the owning side to null (unless already changed)
            if ($disponibilite->getLogement() === $this) {
                $disponibilite->setLogement(null);
            }
        }

        return $this;
    }
}
