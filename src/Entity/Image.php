<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    private ?Logement $logements = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $filename = null;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogements(): ?Logement
    {
        return $this->logements;
    }

    public function setLogements(?Logement $logements): static
    {
        $this->logements = $logements;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function __toString(): string
    {
        return $this->filename ?? '';
    }
}
