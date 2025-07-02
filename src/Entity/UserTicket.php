<?php

namespace App\Entity;

use App\Repository\UserTicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserTicketRepository::class)]
class UserTicket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $nombre = null;

    #[ORM\Column]
    private ?float $prixUnitaire = null;

    #[ORM\Column]
    private ?float $total = null;

    #[ORM\Column]
    private ?float $avance = 0.0;
    
    #[ORM\Column(nullable: true)]
    private ?int $nbMois = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modeEcheance = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $codeOpposition = null;
    
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;
    
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSaisie = null;

    #[ORM\ManyToOne(inversedBy: 'userTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ResponsableTicket $responsable = null;

    #[ORM\ManyToOne(inversedBy: 'userTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?int
    {
        return $this->nombre;
    }

    public function setNombre(int $nombre): static
    {
        $this->nombre = $nombre;

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

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): static
    {
        $this->total = $total;

        return $this;
    }


    public function getPrixUnitaire(): ?float
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(float $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
    }

    public function getResponsable(): ?ResponsableTicket
    {
        return $this->responsable;
    }

    public function setResponsable(?ResponsableTicket $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }

    public function getNbMois(): ?int
    {
        return $this->nbMois;
    }

    public function setNbMois(?int $nbMois): static
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

    public function getDateSaisie(): ?\DateTimeInterface
    {
        return $this->dateSaisie;
    }

    public function setDateSaisie(?\DateTimeInterface $dateSaisie): static
    {
        $this->dateSaisie = $dateSaisie;

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
}
