<?php

namespace App\Entity;

use App\Repository\TripRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TripRepository::class)]
class Trip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $kmdistance = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $traveldate = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $placesoffered = null;

    #[ORM\ManyToMany(targetEntity: Student::class, mappedBy: 'participate')]
    private Collection $participate;

    #[ORM\ManyToOne(inversedBy: 'drive')]
    private ?Student $drive = null;

    #[ORM\ManyToOne(targetEntity: City::class, inversedBy: 'startTrips')]
    private ?City $start = null;

    #[ORM\ManyToOne(targetEntity: City::class, inversedBy: 'arriveTrips')]
    private ?City $arrive = null;

    public function __construct()
    {
        $this->participate = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKmdistance(): ?float
    {
        return $this->kmdistance;
    }

    public function setKmdistance(float $kmdistance): static
    {
        $this->kmdistance = $kmdistance;

        return $this;
    }

    public function getTraveldate(): ?\DateTimeInterface
    {
        return $this->traveldate;
    }

    public function setTraveldate(\DateTimeInterface $traveldate): static
    {
        $this->traveldate = $traveldate;

        return $this;
    }

    public function getPlacesoffered(): ?int
    {
        return $this->placesoffered;
    }

    public function setPlacesoffered(int $placesoffered): static
    {
        $this->placesoffered = $placesoffered;

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getParticipate(): Collection
    {
        return $this->participate;
    }

    public function addParticipate(Student $participate): static
    {
        if (!$this->participate->contains($participate)) {
            $this->participate->add($participate);
            $participate->addParticipate($this);
        }

        return $this;
    }

    public function removeParticipate(Student $participate): static
    {
        if ($this->participate->removeElement($participate)) {
            $participate->removeParticipate($this);
        }

        return $this;
    }

    public function getDrive(): ?Student
    {
        return $this->drive;
    }

    public function setDrive(?Student $drive): static
    {
        $this->drive = $drive;

        return $this;
    }

    public function getStart(): ?City
    {
        return $this->start;
    }

    public function setStart(?City $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function getArrive(): ?City
    {
        return $this->arrive;
    }

    public function setArrive(?City $arrive): static
    {
        $this->arrive = $arrive;

        return $this;
    }
}
