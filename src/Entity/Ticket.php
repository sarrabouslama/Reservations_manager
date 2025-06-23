<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $qte = null;

    #[ORM\Column]
    private ?float $prixUnitaire = null;

    #[ORM\Column]
    private ?float $totalVente = 0;

    #[ORM\Column]
    private ?int $qteVente = 0;

    #[ORM\Column]
    private ?float $totalAvance = 0;

    #[ORM\Column(length: 255, unique:true)]
    private ?string $localisation = null;

    #[ORM\OneToMany(targetEntity: ResponsableTicket::class, mappedBy: 'ticket', orphanRemoval: true)]
    private Collection $responsableTickets;

    public function __construct()
    {
        $this->responsableTickets = new ArrayCollection();
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

    public function getPrixUnitaire(): ?float
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(float $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;

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

    public function getQteVente(): ?int
    {
        return $this->qteVente;
    }

    public function setQteVente(int $qteVente): static
    {
        $this->qteVente = $qteVente;

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

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    /**
     * @return Collection<int, ResponsableTicket>
     */
    public function getResponsableTickets(): Collection
    {
        return $this->responsableTickets;
    }

    public function addResponsableTicket(ResponsableTicket $responsableTicket): static
    {
        if (!$this->responsableTickets->contains($responsableTicket)) {
            $this->responsableTickets->add($responsableTicket);
            $responsableTicket->setTicket($this);
        }

        return $this;
    }

    public function removeResponsableTicket(ResponsableTicket $responsableTicket): static
    {
        if ($this->responsableTickets->removeElement($responsableTicket)) {
            // set the owning side to null (unless already changed)
            if ($responsableTicket->getTicket() === $this) {
                $responsableTicket->setTicket(null);
            }
        }

        return $this;
    }
}
