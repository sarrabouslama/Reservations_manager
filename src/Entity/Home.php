<?php

namespace App\Entity;

use App\Repository\HomeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HomeRepository::class)]
class Home
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $region = null;
    
    #[ORM\Column(length: 255)]
    private ?string $residence = null;

    #[ORM\Column]
    #[Assert\Positive]
    private ?int $nombreChambres = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private ?float $distancePlage = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private ?float $prix = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $bloqued = false;
    
    /**
     * @ORM\OneToMany(targetEntity=HomePeriod::class, mappedBy="home")
     * @ORM\OrderBy({"dateDebut" = "ASC"})
     */
    #[ORM\OneToMany(mappedBy: 'home', targetEntity: HomePeriod::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $homePeriods;

    #[ORM\OneToMany(targetEntity: HomeImage::class, mappedBy: 'home', cascade: ['remove'], orphanRemoval: true)]
    private Collection $images;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mapsUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomProp = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telProp = null;


    public function __construct()
    {
        $this->homePeriods = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom=null): static
    {
        if (!$nom) {
            $this->nom = $this->residence . ' - S+' . $this->nombreChambres;
        }
        else{
            $this->nom = $nom;
        }
        return $this;
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

    public function getNombreChambres(): ?int
    {
        return $this->nombreChambres;
    }

    public function setNombreChambres(int $nombreChambres): static
    {
        $this->nombreChambres = $nombreChambres;
        return $this;
    }

    public function getDistancePlage(): ?float
    {
        return $this->distancePlage;
    }

    public function setDistancePlage(float $distancePlage): static
    {
        $this->distancePlage = $distancePlage;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
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
    
    public function getResidence(): ?string
    {
        return $this->residence;
    }
    
    public function setResidence(string $residence): static
    {
        $this->residence = $residence;
        return $this;
    }

    /**
     * @return Collection<int, HomePeriod>
     */
    public function getHomePeriods(): Collection
    {
        return $this->homePeriods;
    }

    public function addHomePeriod(HomePeriod $homePeriod): static
    {
        if (!$this->homePeriods->contains($homePeriod)) {
            $this->homePeriods->add($homePeriod);
            $homePeriod->setHome($this);
        }
        return $this;
    }

    public function removeHomePeriod(HomePeriod $homePeriod): static
    {
        if ($this->homePeriods->removeElement($homePeriod)) {
            if ($homePeriod->getHome() === $this) {
                $homePeriod->setHome(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, HomeImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(HomeImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setHome($this);
        }
        return $this;
    }

    public function removeImage(HomeImage $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getHome() === $this) {
                $image->setHome(null);
            }
        }
        return $this;
    }
    
    public function isBloqued(): ?bool
    {
        return $this->bloqued;
    }

    public function setBloqued(bool $bloqued): static
    {
        $this->bloqued = $bloqued;

        return $this;
    }

    public function getMapsUrl(): ?string
    {
        return $this->mapsUrl;
    }

    public function setMapsUrl(?string $mapsUrl): static
    {
        $this->mapsUrl = $mapsUrl;

        return $this;
    }

    public function getNomProp(): ?string
    {
        return $this->nomProp;
    }

    public function setNomProp(?string $nomProp): static
    {
        $this->nomProp = $nomProp;

        return $this;
    }

    public function getTelProp(): ?string
    {
        return $this->telProp;
    }

    public function setTelProp(?string $telProp): static
    {
        $this->telProp = $telProp;

        return $this;
    }
} 