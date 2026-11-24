<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $invoiceDate = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $garage = null;

    #[ORM\Column(nullable: true)]
    private ?int $mileage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $totalTTC = null;

    #[ORM\Column(length: 255)]
    private ?string $invoicePath = null;

    #[ORM\Column(length: 255)]
    private ?string $invoiceOriginalName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $observations = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Vehicle $vehicle = null;

    public const TYPES = [
        'Révision / Entretien' => 'revision',
        'Pneumatiques' => 'pneus',
        'Freinage' => 'freins',
        'Réparation / Panne' => 'reparation',
        'Contrôle technique' => 'controle_technique',
        'Autre' => 'autre',
    ];

    #[ORM\Column(length: 30, options: ['default' => 'autre'])]
    private string $type = 'autre';

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

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

    public function getInvoiceDate(): ?\DateTime
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(\DateTime $invoiceDate): static
    {
        $this->invoiceDate = $invoiceDate;

        return $this;
    }

    public function getGarage(): ?string
    {
        return $this->garage;
    }

    public function setGarage(?string $garage): static
    {
        $this->garage = $garage;

        return $this;
    }

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(?int $mileage): static
    {
        $this->mileage = $mileage;

        return $this;
    }

    public function getTotalTTC(): ?string
    {
        return $this->totalTTC;
    }

    public function setTotalTTC(?string $totalTTC): static
    {
        $this->totalTTC = $totalTTC;

        return $this;
    }

    public function getInvoicePath(): ?string
    {
        return $this->invoicePath;
    }

    public function setInvoicePath(string $invoicePath): static
    {
        $this->invoicePath = $invoicePath;

        return $this;
    }

    public function getInvoiceOriginalName(): ?string
    {
        return $this->invoiceOriginalName;
    }

    public function setInvoiceOriginalName(string $invoiceOriginalName): static
    {
        $this->invoiceOriginalName = $invoiceOriginalName;

        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): static
    {
        $this->observations = $observations;

        return $this;
    }
}
