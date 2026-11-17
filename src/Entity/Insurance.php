<?php

namespace App\Entity;

use App\Repository\InsuranceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InsuranceRepository::class)]
class Insurance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $insurer = null;

    #[ORM\Column(length: 40)]
    private ?string $contractNumber = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $endDate = null;

    #[ORM\Column(length: 255)]
    private ?string $insurancePath = null;

    #[ORM\Column(length: 255)]
    private ?string $insuranceOriginalName = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $totalAmount = null;

    #[ORM\ManyToOne(inversedBy: 'insurances')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * @var Collection<int, Covered>
     */
    #[ORM\OneToMany(targetEntity: Covered::class, mappedBy: 'insurance')]
    private Collection $coverages;

    public function __construct()
    {
        $this->coverages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInsurer(): ?string
    {
        return $this->insurer;
    }

    public function setInsurer(string $insurer): static
    {
        $this->insurer = $insurer;

        return $this;
    }

    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(string $contractNumber): static
    {
        $this->contractNumber = $contractNumber;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getInsurancePath(): ?string
    {
        return $this->insurancePath;
    }

    public function setInsurancePath(string $insurancePath): static
    {
        $this->insurancePath = $insurancePath;

        return $this;
    }

    public function getInsuranceOriginalName(): ?string
    {
        return $this->insuranceOriginalName;
    }

    public function setInsuranceOriginalName(string $insuranceOriginalName): static
    {
        $this->insuranceOriginalName = $insuranceOriginalName;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
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
            $coverage->setInsurance($this);
        }

        return $this;
    }

    public function removeCoverage(Covered $coverage): static
    {
        if ($this->coverages->removeElement($coverage)) {
            // set the owning side to null (unless already changed)
            if ($coverage->getInsurance() === $this) {
                $coverage->setInsurance(null);
            }
        }

        return $this;
    }
}
