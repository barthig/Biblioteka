<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: 'App\\Repository\\BookCopyRepository')]
class BookCopy
{
    public const STATUS_AVAILABLE = 'AVAILABLE';
    public const STATUS_RESERVED = 'RESERVED';
    public const STATUS_BORROWED = 'BORROWED';
    public const STATUS_MAINTENANCE = 'MAINTENANCE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['inventory:read', 'loan:read', 'reservation:read', 'order:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'inventory')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Book $book;

    #[ORM\Column(type: 'string', length: 60, unique: true)]
    #[Groups(['inventory:read', 'loan:read', 'reservation:read', 'order:read'])]
    private string $inventoryCode;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['inventory:read', 'reservation:read', 'order:read'])]
    private string $status = self::STATUS_AVAILABLE;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    #[Groups(['inventory:read', 'reservation:read', 'order:read'])]
    private ?string $location = null;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    #[Groups(['inventory:read', 'reservation:read', 'order:read'])]
    #[SerializedName('condition')]
    private ?string $conditionState = null;

    #[ORM\Column(type: 'datetime_immutable')]
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

    public function getBook(): Book
    {
        return $this->book;
    }

    public function setBook(Book $book): self
    {
        $this->book = $book;
        return $this;
    }

    public function getInventoryCode(): string
    {
        return $this->inventoryCode;
    }

    public function setInventoryCode(string $inventoryCode): self
    {
        $this->inventoryCode = $inventoryCode;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_AVAILABLE, self::STATUS_RESERVED, self::STATUS_BORROWED, self::STATUS_MAINTENANCE], true)) {
            throw new \InvalidArgumentException('Invalid book copy status: ' . $status);
        }
        $this->status = $status;
        $this->touch();
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        $this->touch();
        return $this;
    }

    public function getConditionState(): ?string
    {
        return $this->conditionState;
    }

    public function setConditionState(?string $conditionState): self
    {
        $this->conditionState = $conditionState;
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
