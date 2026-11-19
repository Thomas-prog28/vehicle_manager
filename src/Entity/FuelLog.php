<?php

namespace App\Entity;

use App\Repository\FuelLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FuelLogRepository::class)]
class FuelLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fuelLogs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Vehicle $vehicle = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $filledAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $stationName = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $liters = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private ?string $totalPrice = null;

    #[ORM\Column]
    private ?int $mileage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $consumption = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 3)]
    private ?string $priceLiter = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFilledAt(): ?\DateTimeImmutable
    {
        return $this->filledAt;
    }

    public function setFilledAt(\DateTimeImmutable $filledAt): static
    {
        $this->filledAt = $filledAt;

        return $this;
    }

    public function getStationName(): ?string
    {
        return $this->stationName;
    }

    public function setStationName(?string $stationName): static
    {
        $this->stationName = $stationName;

        return $this;
    }

    public function getLiters(): ?string
    {
        return $this->liters;
    }

    public function setLiters(string $liters): static
    {
        $this->liters = $liters;

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(int $mileage): static
    {
        $this->mileage = $mileage;

        return $this;
    }

    public function getConsumption(): ?string
    {
        return $this->consumption;
    }

    public function setConsumption(?string $consumption): static
    {
        $this->consumption = $consumption;

        return $this;
    }

    public function getPriceLiter(): ?string
    {
        return $this->priceLiter;
    }

    public function setPriceLiter(string $priceLiter): static
    {
        $this->priceLiter = $priceLiter;

        return $this;
    }
}
