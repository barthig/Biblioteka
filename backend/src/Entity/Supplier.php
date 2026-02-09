<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\\Repository\\SupplierRepository')]
class Supplier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['supplier:read', 'acquisition:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    #[Groups(['supplier:read', 'acquisition:read'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    #[Groups(['supplier:read'])]
    private ?string $contactEmail = null;

    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    #[Groups(['supplier:read'])]
    private ?string $contactPhone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['supplier:read'])]
    private ?string $addressLine = null;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    #[Groups(['supplier:read'])]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    #[Groups(['supplier:read'])]
    private ?string $country = null;

    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    #[Groups(['supplier:read'])]
    private ?string $taxIdentifier = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['supplier:read'])]
    private bool $active = true;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['supplier:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        $this->touch();
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail !== null ? strtolower(trim($contactEmail)) : null;
        $this->touch();
        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): self
    {
        $this->contactPhone = $contactPhone !== null ? trim($contactPhone) : null;
        $this->touch();
        return $this;
    }

    public function getAddressLine(): ?string
    {
        return $this->addressLine;
    }

    public function setAddressLine(?string $addressLine): self
    {
        $this->addressLine = $addressLine !== null ? trim($addressLine) : null;
        $this->touch();
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city !== null ? trim($city) : null;
        $this->touch();
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country !== null ? trim($country) : null;
        $this->touch();
        return $this;
    }

    public function getTaxIdentifier(): ?string
    {
        return $this->taxIdentifier;
    }

    public function setTaxIdentifier(?string $taxIdentifier): self
    {
        $this->taxIdentifier = $taxIdentifier !== null ? trim($taxIdentifier) : null;
        $this->touch();
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        $this->touch();
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        $this->touch();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
