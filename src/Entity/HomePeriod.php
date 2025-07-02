<?php

namespace App\Entity;

use App\Repository\HomePeriodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HomePeriodRepository::class)]
class HomePeriod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\ManyToOne(inversedBy: 'homePeriods')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Home $home = null;
    
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\OneToMany(mappedBy: 'homePeriod', targetEntity: Reservation::class, orphanRemoval: true)]
    private Collection $reservations;

    #[ORM\Column]
    private ?int $maxUsers = 0;


    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHome(): ?Home
    {
        return $this->home;
    }

    public function setHome(?Home $home): static
    {
        $this->home = $home;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin = null): static
    {
        if (!$dateFin){
            $this->dateFin = $this->dateDebut ? (clone $this->dateDebut)->add(new \DateInterval('P7D')) : null;
        }
        else{
            $this->dateFin = $dateFin;
        }
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
            $reservation->setHomePeriod($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getHomePeriod() === $this) {
                $reservation->setHomePeriod(null);
            }
        }

        return $this;
    }

    public function getSelectedReservations(): Collection
    {
        return $this->reservations->filter(fn(Reservation $r) => $r->isSelected());
    }

    public function getSelectedUsersCount(): int
    {
        return $this->getSelectedReservations()->count();
    }

    public function isUserSelected(User $user): bool
    {
        foreach ($this->reservations as $reservation) {
            if ($reservation->getUser() === $user && $reservation->isSelected()) {
                return true;
            }
        }
        return false;
    }

    public function getMaxUsers(): ?int
    {
        return $this->maxUsers;
    }

    public function setMaxUsers(int $maxUsers): static
    {
        $this->maxUsers = $maxUsers;

        return $this;
    }
} 