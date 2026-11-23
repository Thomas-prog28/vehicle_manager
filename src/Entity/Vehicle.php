<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\BridgeDoctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_PLATE', fields: ['user', 'plate'])]
#[UniqueEntity(fields: ['user', 'plate'], message: 'Ce véhicule est déjà enregistré.', errorPath: 'plate')]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $type = null;

    #[ORM\Column(length: 50)]
    private ?string $brand = null;

    #[ORM\Column(length: 50)]
    private ?string $model = null;

    #[ORM\Column(length: 12)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^([A-Z]{2}-\d{3}-[A-Z]{2}|\d{1,4} [A-Z]{1,3} (\d{2,3}|2[AB]))$/', message: 'Format attendu : AB-123-CD (depuis 2009) ou 1234 AB 56 (avant 2009).')]
    private ?string $plate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $firstRegistrationDate = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $archived = false;

    #[ORM\Column(nullable: true)]
    private ?int $serviceIntervalKm = null;

    #[ORM\Column(nullable: true)]
    private ?int $serviceIntervalMonths = null;


    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * @var Collection<int, Covered>
     */
    #[ORM\OneToMany(targetEntity: Covered::class, mappedBy: 'vehicle')]
    private Collection $coverages;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'vehicle')]
    #[ORM\OrderBy(['invoiceDate' => 'DESC', 'id' => 'DESC'])]
    private Collection $invoices;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $fuelType = null;

    /**
     * @var Collection<int, FuelLog>
     */
    #[ORM\OneToMany(targetEntity: FuelLog::class, mappedBy: 'vehicle', orphanRemoval: true)]
    #[ORM\OrderBy(['filledAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $fuelLogs;

    public function __construct()
    {
        $this->invoices = new ArrayCollection();
        $this->coverages = new ArrayCollection();
        $this->fuelLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getPlate(): ?string
    {
        return $this->plate;
    }

    public function setPlate(string $plate): static
    {
        // Séparateurs unifiés, majuscules
        $clean = strtoupper(trim(preg_replace('/[\s\-]+/', ' ', $plate)));

        if (preg_match('/^([A-Z]{2}) ?(\d{3}) ?([A-Z]{2})$/', $clean, $m)) {
            $clean = $m[1] . '-' . $m[2] . '-' . $m[3];          // SIV : AB-123-CD
        } elseif (preg_match('/^(\d{1,4}) ?([A-Z]{1,3}) ?(\d{2,3}|2[AB])$/', $clean, $m)) {
            $clean = $m[1] . ' ' . $m[2] . ' ' . $m[3];          // ancien : 1234 AB 56
        }

        $this->plate = $clean;

        return $this;
    }

    public function getFirstRegistrationDate(): ?\DateTime
    {
        return $this->firstRegistrationDate;
    }

    public function setFirstRegistrationDate(?\DateTime $firstRegistrationDate): static
    {
        $this->firstRegistrationDate = $firstRegistrationDate;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): static
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setVehicle($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getVehicle() === $this) {
                $invoice->setVehicle(null);
            }
        }

        return $this;
    }

    public function getCurrentMileage(): int
    {
        $invoiceKm = 0;
        foreach ($this->invoices as $invoice) {
            if ($invoice->getMileage() !== null) {
                $invoiceKm = $invoice->getMileage();
                break;
            }
        }

        $fuelKm = 0;
        foreach ($this->fuelLogs as $fuelLog) {
            if ($fuelLog->getMileage() !== null) {
                $fuelKm = $fuelLog->getMileage();
                break;
            }
        }

        return max($invoiceKm, $fuelKm);
    }


    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Covered>
     */
    public function getCoverages(): Collection
    {
        return $this->coverages;
    }

    public function addCoverage(Covered $coverage): static
    {
        if (!$this->coverages->contains($coverage)) {
            $this->coverages->add($coverage);
            $coverage->setVehicle($this);
        }

        return $this;
    }

    public function removeCoverage(Covered $coverage): static
    {
        if ($this->coverages->removeElement($coverage)) {
            // set the owning side to null (unless already changed)
            if ($coverage->getVehicle() === $this) {
                $coverage->setVehicle(null);
            }
        }

        return $this;
    }

    public function getFuelType(): ?string
    {
        return $this->fuelType;
    }

    public function setFuelType(?string $fuelType): static
    {
        $this->fuelType = $fuelType;

        return $this;
    }

    /**
     * @return Collection<int, FuelLog>
     */
    public function getFuelLogs(): Collection
    {
        return $this->fuelLogs;
    }

    public function addFuelLog(FuelLog $fuelLog): static
    {
        if (!$this->fuelLogs->contains($fuelLog)) {
            $this->fuelLogs->add($fuelLog);
            $fuelLog->setVehicle($this);
        }

        return $this;
    }

    public function removeFuelLog(FuelLog $fuelLog): static
    {
        if ($this->fuelLogs->removeElement($fuelLog)) {
            // set the owning side to null (unless already changed)
            if ($fuelLog->getVehicle() === $this) {
                $fuelLog->setVehicle(null);
            }
        }

        return $this;
    }

    /**
     * Retourne les contrats d'assurance triés par date d'échéance décroissante (la plus lointaine/récente en premier).
     *
     * @return Covered[]
     */
    public function getSortedCoverages(): array
    {
        $coverages = $this->coverages->toArray();

        usort($coverages, static function (Covered $a, Covered $b): int {
            $dateA = $a->getInsurance()?->getEndDate();
            $dateB = $b->getInsurance()?->getEndDate();

            return $dateB <=> $dateA; // Ordre décroissant (du plus récent/lointain au plus ancien)
        });

        return $coverages;
    }

    public function getServiceIntervalKm(): ?int
    {
        return $this->serviceIntervalKm;
    }

    public function setServiceIntervalKm(?int $serviceIntervalKm): static
    {
        $this->serviceIntervalKm = $serviceIntervalKm;

        return $this;
    }

    public function getServiceIntervalMonths(): ?int
    {
        return $this->serviceIntervalMonths;
    }

    public function setServiceIntervalMonths(?int $serviceIntervalMonths): static
    {
        $this->serviceIntervalMonths = $serviceIntervalMonths;

        return $this;
    }

    /**
     * Calcule la prochaine révision à partir de la dernière facture de catégorie 'revision'.
     *
     * @return array{nextKm: ?int, remainingKm: ?int, nextDate: ?\DateTimeInterface, isUrgent: bool, isOverdue: bool}
     */
    public function getNextServiceInfo(): array
    {
        $intervalKm = $this->serviceIntervalKm ?? (strtolower((string) $this->type) === 'moto' ? 6000 : 15000);
        $intervalMonths = $this->serviceIntervalMonths ?? 12;

        // Recherche la facture de type 'revision' la plus récente
        $lastRevision = null;
        foreach ($this->invoices as $invoice) {
            if ($invoice->getType() === 'revision') {
                if ($lastRevision === null || $invoice->getInvoiceDate() > $lastRevision->getInvoiceDate()) {
                    $lastRevision = $invoice;
                }
            }
        }

        if (!$lastRevision) {
            return [
                'nextKm' => null,
                'remainingKm' => null,
                'nextDate' => null,
                'isUrgent' => false,
                'isOverdue' => false,
            ];
        }

        $nextKm = $lastRevision->getMileage() !== null
            ? $lastRevision->getMileage() + $intervalKm
            : null;

        $remainingKm = $nextKm !== null
            ? $nextKm - $this->getCurrentMileage()
            : null;

        $nextDate = null;
        if ($lastRevision->getInvoiceDate() !== null) {
            $baseDate = \DateTimeImmutable::createFromInterface($lastRevision->getInvoiceDate());
            $nextDate = $baseDate->modify("+{$intervalMonths} months");
        }

        $today = new \DateTimeImmutable('today');
        $in30Days = $today->modify('+30 days');

        $isOverdue = ($remainingKm !== null && $remainingKm <= 0)
            || ($nextDate !== null && $nextDate < $today);

        $isUrgent = $isOverdue
            || ($remainingKm !== null && $remainingKm <= 500)
            || ($nextDate !== null && $nextDate <= $in30Days);

        return [
            'nextKm' => $nextKm,
            'remainingKm' => $remainingKm,
            'nextDate' => $nextDate,
            'isUrgent' => $isUrgent,
            'isOverdue' => $isOverdue,
        ];
    }
}
