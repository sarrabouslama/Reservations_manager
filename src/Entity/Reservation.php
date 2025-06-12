<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[UniqueEntity(
    fields: ['user', 'homePeriod'],
    message: 'Vous avez déjà fait une réservation pour cette période.'
)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?HomePeriod $homePeriod = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column]
    private ?bool $estBloque = false;

    #[ORM\Column]
    private ?bool $isSelected = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSelection = null;

    #[ORM\Column]
    private ?bool $isConfirmed = false;

    #[ORM\OneToOne(inversedBy: 'reservation')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->dateReservation = new \DateTime();
        $this->estBloque = false;
        $this->isSelected = false;
        $this->isConfirmed = false;
        $this->dateSelection = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHomePeriod(): ?HomePeriod
    {
        return $this->homePeriod;
    }

    public function setHomePeriod(?HomePeriod $homePeriod): static
    {
        $this->homePeriod = $homePeriod;
        return $this;
    }

    public function getDateReservation(): ?\DateTimeInterface
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTimeInterface $dateReservation): static
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }

    public function isEstBloque(): ?bool
    {
        return $this->estBloque;
    }

    public function setEstBloque(bool $estBloque): static
    {
        $this->estBloque = $estBloque;
        return $this;
    }

    public function isSelected(): ?bool
    {
        return $this->isSelected;
    }

    public function setIsSelected(bool $isSelected): static
    {
        $this->isSelected = $isSelected;
        if ($isSelected) {
            $this->dateSelection = new \DateTime();
        } else {
            $this->dateSelection = null;
        }
        return $this;
    }

    public function getDateSelection(): ?\DateTimeInterface
    {
        return $this->dateSelection;
    }
    
    public function setDateSelection(?\DateTimeInterface $dateSelection): static
    {
        $this->dateSelection = $dateSelection;
        return $this;
    }
    

    public function getHome(): ?Home
    {
        return $this->homePeriod?->getHome();
    }

    public function isConfirmed(): ?bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(bool $isConfirmed): static
    {
        $this->isConfirmed = $isConfirmed;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
} 