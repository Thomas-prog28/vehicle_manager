<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $loginCount = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;


    /**
     * @var Collection<int, Vehicle>
     */
    #[ORM\OneToMany(targetEntity: Vehicle::class, mappedBy: 'user')]
    private Collection $vehicles;

    /**
     * @var Collection<int, Insurance>
     */
    #[ORM\OneToMany(targetEntity: Insurance::class, mappedBy: 'user')]
    private Collection $insurances;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
        $this->insurances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    /**
     * @return Collection<int, Vehicle>
     */
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): static
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles->add($vehicle);
            $vehicle->setUser($this);
        }

        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): static
    {
        if ($this->vehicles->removeElement($vehicle)) {
            // set the owning side to null (unless already changed)
            if ($vehicle->getUser() === $this) {
                $vehicle->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Insurance>
     */
    public function getInsurances(): Collection
    {
        return $this->insurances;
    }

    public function addInsurance(Insurance $insurance): static
    {
        if (!$this->insurances->contains($insurance)) {
            $this->insurances->add($insurance);
            $insurance->setUser($this);
        }

        return $this;
    }

    public function removeInsurance(Insurance $insurance): static
    {
        if ($this->insurances->removeElement($insurance)) {
            // set the owning side to null (unless already changed)
            if ($insurance->getUser() === $this) {
                $insurance->setUser(null);
            }
        }
        return $this;
    }

    public function getLoginCount(): int
    {
        return $this->loginCount;
    }

    public function setLoginCount(int $loginCount): static
    {
        $this->loginCount = $loginCount;

        return $this;
    }

    public function incrementLoginCount(): static
    {
        $this->loginCount++;
        $this->lastLoginAt = new \DateTimeImmutable();

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    /**
     * Compte le nombre total de fichiers (factures + attestations d'assurance) téléversés par cet utilisateur.
     */
    public function getUploadedFilesCount(): int
    {
        $count = 0;

        foreach ($this->vehicles as $vehicle) {
            foreach ($vehicle->getInvoices() as $invoice) {
                if ($invoice->getInvoicePath() !== null) {
                    $count++;
                }
            }
        }

        foreach ($this->insurances as $insurance) {
            if ($insurance->getInsurancePath() !== null) {
                $count++;
            }
        }

        return $count;
    }
}
