<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\File\File;


#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tel = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255, unique:true)]
    private ?string $matricule = null;

    #[ORM\Column(length: 8)]
    #[Assert\Length(
        min: 8,
        max: 8,
        exactMessage: 'Le CIN doit contenir exactement 8 caractères.'
    )]
    private ?string $cin = null;

    #[ORM\Column]
    private ?bool $lastYear = false;
    
    #[ORM\Column]
    private ?bool $actif = true;
    
    #[ORM\Column(length: 255)]
    private ?string $sit = null;
    
    #[ORM\Column]
    private ?int $Nb_enfants = null;
    
    #[ORM\Column(length: 255)]
    private ?string $emploi = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Matricule_cnss = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $direction = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;
    
    private ?File $imageFile = null;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Reservation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?Reservation $reservation = null;

    public function __construct()
    {
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->matricule;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): static
    {
        $this->matricule = $matricule;

        return $this;
    }

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(string $cin): static
    {
        $this->cin = $cin;

        return $this;
    }

    public function isLastYear(): ?bool
    {
        return $this->lastYear;
    }

    public function setLastYear(bool $lastYear): static
    {
        $this->lastYear = $lastYear;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): static
    {
        $this->tel = $tel;

        return $this;
    }

    public function getSit(): ?string
    {
        return $this->sit;
    }

    public function setSit(string $sit): static
    {
        $this->sit = $sit;

        return $this;
    }

    public function getEmploi(): ?string
    {
        return $this->emploi;
    }

    public function setEmploi(string $emploi): static
    {
        $this->emploi = $emploi;

        return $this;
    }

    public function getNbEnfants(): ?int
    {
        return $this->Nb_enfants;
    }

    public function setNbEnfants(int $Nb_enfants): static
    {
        $this->Nb_enfants = $Nb_enfants;

        return $this;
    }

    public function getMatriculeCnss(): ?string
    {
        return $this->Matricule_cnss;
    }

    public function setMatriculeCnss(?string $Matricule_cnss): static
    {
        $this->Matricule_cnss = $Matricule_cnss;

        return $this;
    }

    public function getDirection(): ?string
    {
        return $this->direction;
    }

    public function setDirection(?string $direction): static
    {
        $this->direction = $direction;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }
    
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): static
    {
        $this->imageFile = $imageFile;
        return $this;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): self
    {
        $this->reservation = $reservation;
        return $this;
    }

}