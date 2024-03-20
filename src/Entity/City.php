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

//    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'start')]
//    private Collection $trips;

    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'start')]
    private Collection $startTrips;

    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'arrive')]
    private Collection $arriveTrips;

    public function __construct()
    {
        $this->live = new ArrayCollection();
//        $this->trips = new ArrayCollection();
        $this->startTrips = new ArrayCollection();
        $this->arriveTrips = new ArrayCollection();
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
    public function getStartTrips(): Collection
    {
        return $this->startTrips;
    }

    public function addStartTrip(Trip $trip): static
    {
        if (!$this->startTrips->contains($trip)) {
            $this->startTrips->add($trip);
            $trip->setStart($this);
        }

        return $this;
    }

//    public function removeTrip(Trip $trip): static
//    {
//        if ($this->trips->removeElement($trip)) {
//            // set the owning side to null (unless already changed)
//            if ($trip->getStart() === $this) {
//                $trip->setStart(null);
//            }
//        }
//
//        return $this;
//    }

    public function removeStartTrip(Trip $trip): static
    {
        if ($this->startTrips->removeElement($trip)) {
            // set the owning side to null (unless already changed)
            if ($trip->getStart() === $this) {
                $trip->setStart(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Trip>
     */
    public function getArriveTrips(): Collection
    {
        return $this->arriveTrips;
    }

    public function addArriveTrip(Trip $trip): static
    {
        if (!$this->arriveTrips->contains($trip)) {
            $this->arriveTrips->add($trip);
            $trip->setArrive($this);
        }

        return $this;
    }

    public function removeArriveTrip(Trip $trip): static
    {
        if ($this->arriveTrips->removeElement($trip)) {
            // set the owning side to null (unless already changed)
            if ($trip->getArrive() === $this) {
                $trip->setArrive(null);
            }
        }

        return $this;
    }
}
