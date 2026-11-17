<?php

namespace App\Entity;

use App\Repository\CoveredRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoveredRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_VEHICLE_INSURANCE', fields: ['vehicle', 'insurance'])]
class Covered
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'coverages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne(inversedBy: 'coverages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Insurance $insurance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $premium = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPremium(): ?string
    {
        return $this->premium;
    }

    public function setPremium(string $premium): static
    {
        $this->premium = $premium;

        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): static
    {
        $this->vehicle = $vehicle;

        return $this;
    }

    public function getInsurance(): ?Insurance
    {
        return $this->insurance;
    }

    public function setInsurance(?Insurance $insurance): static
    {
        $this->insurance = $insurance;

        return $this;
    }
}
