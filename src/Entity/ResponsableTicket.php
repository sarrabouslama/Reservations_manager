<?php

namespace App\Entity;

use App\Repository\ResponsableTicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResponsableTicketRepository::class)]
class ResponsableTicket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $qte = null;

    #[ORM\Column]
    private ?int $qteVente = 0;

    #[ORM\Column]
    private ?float $totalVente = 0;

    #[ORM\Column]
    private ?float $totalAvance = 0;

    #[ORM\OneToMany(targetEntity: UserTicket::class, mappedBy: 'responsable')]
    private Collection $userTickets;

    #[ORM\ManyToOne(inversedBy: 'responsableTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(inversedBy: 'responsableTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $responsable = null;

    public function __construct()
    {
        $this->userTickets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQte(): ?int
    {
        return $this->qte;
    }

    public function setQte(int $qte): static
    {
        $this->qte = $qte;

        return $this;
    }

    public function getQteVente(): ?int
    {
        return $this->qteVente;
    }

    public function setQteVente(int $qteVente): static
    {
        $this->qteVente = $qteVente;

        return $this;
    }

    public function getTotalVente(): ?float
    {
        return $this->totalVente;
    }

    public function setTotalVente(float $totalVente): static
    {
        $this->totalVente = $totalVente;

        return $this;
    }

    /**
     * @return Collection<int, UserTicket>
     */
    public function getUserTickets(): Collection
    {
        return $this->userTickets;
    }

    public function addUserTicket(UserTicket $userTicket): static
    {
        if (!$this->userTickets->contains($userTicket)) {
            $this->userTickets->add($userTicket);
            $userTicket->setResponsable($this);
        }

        return $this;
    }

    public function removeUserTicket(UserTicket $userTicket): static
    {
        if ($this->userTickets->removeElement($userTicket)) {
            // set the owning side to null (unless already changed)
            if ($userTicket->getResponsable() === $this) {
                $userTicket->setResponsable(null);
            }
        }

        return $this;
    }

    public function getTotalAvance(): ?float
    {
        return $this->totalAvance;
    }

    public function setTotalAvance(float $totalAvance): static
    {
        $this->totalAvance = $totalAvance;

        return $this;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): static
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getResponsable(): ?User
    {
        return $this->responsable;
    }

    public function setResponsable(?User $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }
}
