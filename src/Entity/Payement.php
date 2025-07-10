<?php

namespace App\Entity;

use App\Repository\PayementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PayementRepository::class)]
class Payement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $montantGlobal = null;

    #[ORM\Column]
    private ?float $avance = 0.0;

    #[ORM\Column]
    private ?int $nbMois = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modeEcheance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $codeOpposition = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\OneToOne(inversedBy: 'payement', targetEntity: Reservation::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Reservation $reservation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateSaisie = null;

    #[ORM\OneToOne(inversedBy: 'payement', cascade: ['persist', 'remove'])]
    private ?PiscineReservation $piscineReservation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontantGlobal(): ?float
    {
        return $this->montantGlobal;
    }

    public function setMontantGlobal(float $montantGlobal): static
    {
        $this->montantGlobal = $montantGlobal;

        return $this;
    }

    public function getAvance(): ?float
    {
        return $this->avance;
    }

    public function setAvance(float $avance): static
    {
        $this->avance = $avance;

        return $this;
    }

    public function getNbMois(): ?int
    {
        return $this->nbMois;
    }

    public function setNbMois(int $nbMois): static
    {
        $this->nbMois = $nbMois;

        return $this;
    }

    public function getModeEcheance(): ?string
    {
        return $this->modeEcheance;
    }

    public function setModeEcheance(?string $modeEcheance): static
    {
        $this->modeEcheance = $modeEcheance;

        return $this;
    }

    public function getCodeOpposition(): ?string
    {
        return $this->codeOpposition;
    }

    public function setCodeOpposition(?string $codeOpposition): static
    {
        $this->codeOpposition = $codeOpposition;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): static
    {
        $this->reservation = $reservation;
        if ($reservation && $reservation->getPayement() !== $this) {
            $reservation->setPayement($this);
        }
        return $this;
    }

    public function getDateSaisie(): ?\DateTimeInterface
    {
        return $this->dateSaisie;
    }

    public function setDateSaisie(\DateTimeInterface $dateSaisie): static
    {
        $this->dateSaisie = $dateSaisie;

        return $this;
    }

    public function getPiscineReservation(): ?PiscineReservation
    {
        return $this->piscineReservation;
    }

    public function setPiscineReservation(?PiscineReservation $piscineReservation): static
    {
        $this->piscineReservation = $piscineReservation;

        return $this;
    }
}
