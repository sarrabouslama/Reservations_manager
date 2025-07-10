<?php

namespace App\Entity;

use App\Repository\PiscineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PiscineRepository::class)]
class Piscine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $region = null;

    #[ORM\Column(length: 255)]
    private ?string $hotel = null;

    #[ORM\Column]
    private ?float $prixInitial = 0;

    #[ORM\Column]
    private ?float $consommation = 0;

    #[ORM\Column]
    private ?float $amicale = 0;

    #[ORM\Column]
    private ?float $prixFinal = 0;

    #[ORM\Column]
    private ?float $avance = 0;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateLimite = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $entree = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sortie = null;

    #[ORM\Column]
    private ?int $nbEnfants = 0;

    #[ORM\Column]
    private ?int $nbAdultes = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $nbPersonnes = 0;

    #[ORM\OneToMany(targetEntity: PiscineReservation::class, mappedBy: 'piscine', orphanRemoval: true)]
    private Collection $reservations;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getHotel(): ?string
    {
        return $this->hotel;
    }

    public function setHotel(string $hotel): static
    {
        $this->hotel = $hotel;

        return $this;
    }

    public function getPrixInitial(): ?float
    {
        return $this->prixInitial;
    }

    public function setPrixInitial(float $prixInitial): static
    {
        $this->prixInitial = $prixInitial;

        return $this;
    }

    public function getConsommation(): ?float
    {
        return $this->consommation;
    }

    public function setConsommation(float $consommation): static
    {
        $this->consommation = $consommation;

        return $this;
    }

    public function getAmicale(): ?float
    {
        return $this->amicale;
    }

    public function setAmicale(float $amicale): static
    {
        $this->amicale = $amicale;

        return $this;
    }

    public function getPrixFinal(): ?float
    {
        return $this->prixFinal;
    }

    public function setPrixFinal(float $prixFinal): static
    {
        $this->prixFinal = $prixFinal;

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

    public function getDateLimite(): ?\DateTimeInterface
    {
        return $this->dateLimite;
    }

    public function setDateLimite(?\DateTimeInterface $dateLimite): static
    {
        $this->dateLimite = $dateLimite;

        return $this;
    }

    public function getEntree(): ?\DateTimeInterface
    {
        return $this->entree;
    }

    public function setEntree(?\DateTimeInterface $entree): static
    {
        $this->entree = $entree;

        return $this;
    }

    public function getSortie(): ?\DateTimeInterface
    {
        return $this->sortie;
    }

    public function setSortie(?\DateTimeInterface $sortie): static
    {
        $this->sortie = $sortie;

        return $this;
    }

    public function getNbEnfants(): ?int
    {
        return $this->nbEnfants;
    }

    public function setNbEnfants(int $nbEnfants): static
    {
        $this->nbEnfants = $nbEnfants;

        return $this;
    }

    public function getNbAdultes(): ?int
    {
        return $this->nbAdultes;
    }

    public function setNbAdultes(int $nbAdultes): static
    {
        $this->nbAdultes = $nbAdultes;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getNbPersonnes(): ?int
    {
        return $this->nbPersonnes;
    }

    public function setNbPersonnes(?int $nbPersonnes): static
    {
        $this->nbPersonnes = $nbPersonnes;

        return $this;
    }

    /**
     * @return Collection<int, PiscineReservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(PiscineReservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setPiscine($this);
        }

        return $this;
    }

    public function removeReservation(PiscineReservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getPiscine() === $this) {
                $reservation->setPiscine(null);
            }
        }

        return $this;
    }
}
