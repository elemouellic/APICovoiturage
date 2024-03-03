<?php

namespace App\Entity;

use App\Repository\CityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CityRepository::class)]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 5)]
    private ?string $zipcode = null;

    #[ORM\OneToMany(targetEntity: Student::class, mappedBy: 'live')]
    private Collection $live;

    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'start')]
    private Collection $trips;

    public function __construct()
    {
        $this->live = new ArrayCollection();
        $this->trips = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(string $zipcode): static
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getLive(): Collection
    {
        return $this->live;
    }

    public function addLive(Student $live): static
    {
        if (!$this->live->contains($live)) {
            $this->live->add($live);
            $live->setLive($this);
        }

        return $this;
    }

    public function removeLive(Student $live): static
    {
        if ($this->live->removeElement($live)) {
            // set the owning side to null (unless already changed)
            if ($live->getLive() === $this) {
                $live->setLive(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Trip>
     */
    public function getTrips(): Collection
    {
        return $this->trips;
    }

    public function addTrip(Trip $trip): static
    {
        if (!$this->trips->contains($trip)) {
            $this->trips->add($trip);
            $trip->setStart($this);
        }

        return $this;
    }

    public function removeTrip(Trip $trip): static
    {
        if ($this->trips->removeElement($trip)) {
            // set the owning side to null (unless already changed)
            if ($trip->getStart() === $this) {
                $trip->setStart(null);
            }
        }

        return $this;
    }
}
