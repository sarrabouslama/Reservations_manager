<?php

namespace App\Entity;

use App\Repository\PiscineReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PiscineReservationRepository::class)]
class PiscineReservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Piscine $piscine = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column]
    private ?bool $selected = null;

    #[ORM\Column]
    private ?bool $confirmed = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSelection = null;

    #[ORM\OneToOne(inversedBy: 'piscineReservation')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToOne(mappedBy: 'piscineReservation', cascade: ['persist', 'remove'])]
    private ?Payement $payement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $receiptFilename = null;

    public function __construct()
    {
        $this->dateReservation = new \DateTime();
        $this->selected = false;
        $this->confirmed = false;
        $this->dateSelection = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPiscine(): ?Piscine
    {
        return $this->piscine;
    }

    public function setPiscine(?Piscine $piscine): static
    {
        $this->piscine = $piscine;

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

    public function isSelected(): ?bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): static
    {
        $this->selected = $selected;
        if ($selected) {
            $this->dateSelection = new \DateTime();
        } else {
            $this->dateSelection = null;
        }
        return $this;
    }

    public function isConfirmed(): ?bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): static
    {
        $this->confirmed = $confirmed;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        $user->setPiscineReservation($this);

        return $this;
    }

    public function getPayement(): ?Payement
    {
        return $this->payement;
    }

    public function setPayement(?Payement $payement): static
    {
        // unset the owning side of the relation if necessary
        if ($payement === null && $this->payement !== null) {
            $this->payement->setPiscineReservation(null);
        }

        // set the owning side of the relation if necessary
        if ($payement !== null && $payement->getPiscineReservation() !== $this) {
            $payement->setPiscineReservation($this);
        }

        $this->payement = $payement;

        return $this;
    }

    public function getReceiptFilename(): ?string
    {
        return $this->receiptFilename;
    }

    public function setReceiptFilename(?string $receiptFilename): static
    {
        $this->receiptFilename = $receiptFilename;

        return $this;
    }
}
