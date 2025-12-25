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
    public const STATUS_WITHDRAWN = 'WITHDRAWN';

    public const ACCESS_OPEN_STACK = 'OPEN_STACK';
    public const ACCESS_STORAGE = 'STORAGE';
    public const ACCESS_REFERENCE = 'REFERENCE';

    private const STATUS_ALIASES = [
        'available' => self::STATUS_AVAILABLE,
        'reserved' => self::STATUS_RESERVED,
        'loaned' => self::STATUS_BORROWED,
        'borrowed' => self::STATUS_BORROWED,
        'maintenance' => self::STATUS_MAINTENANCE,
        'withdrawn' => self::STATUS_WITHDRAWN,
    ];

    private const ACCESS_TYPE_ALIASES = [
        'open_stack' => self::ACCESS_OPEN_STACK,
        'open stack' => self::ACCESS_OPEN_STACK,
        'storage' => self::ACCESS_STORAGE,
        'closed_stack' => self::ACCESS_STORAGE,
        'reference' => self::ACCESS_REFERENCE,
        'digital' => self::ACCESS_REFERENCE,
    ];

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

    #[ORM\Column(type: 'string', length: 30)]
    #[Groups(['inventory:read', 'reservation:read', 'order:read'])]
    private string $accessType = self::ACCESS_STORAGE;

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
        return self::normalizeStatus($this->status) ?? $this->status;
    }

    public function setStatus(string $status): self
    {
        $normalized = self::normalizeStatus($status);
        if (!in_array($normalized, [self::STATUS_AVAILABLE, self::STATUS_RESERVED, self::STATUS_BORROWED, self::STATUS_MAINTENANCE, self::STATUS_WITHDRAWN], true)) {
            throw new \InvalidArgumentException('Invalid book copy status: ' . $status);
        }
        $this->status = $normalized;
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

    public function getAccessType(): string
    {
        return self::normalizeAccessType($this->accessType) ?? $this->accessType;
    }

    public function setAccessType(string $accessType): self
    {
        $accessType = self::normalizeAccessType($accessType) ?? strtoupper(trim($accessType));
        if (!in_array($accessType, [self::ACCESS_OPEN_STACK, self::ACCESS_STORAGE, self::ACCESS_REFERENCE], true)) {
            throw new \InvalidArgumentException('Invalid access type: ' . $accessType);
        }

        $this->accessType = $accessType;
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

    private static function normalizeStatus(string $status): ?string
    {
        $status = trim($status);
        if ($status === '') {
            return null;
        }
        $lower = strtolower($status);
        if (isset(self::STATUS_ALIASES[$lower])) {
            return self::STATUS_ALIASES[$lower];
        }
        $upper = strtoupper($status);
        if (in_array($upper, [self::STATUS_AVAILABLE, self::STATUS_RESERVED, self::STATUS_BORROWED, self::STATUS_MAINTENANCE, self::STATUS_WITHDRAWN], true)) {
            return $upper;
        }
        return null;
    }

    private static function normalizeAccessType(string $accessType): ?string
    {
        $accessType = trim($accessType);
        if ($accessType === '') {
            return null;
        }
        $lower = strtolower($accessType);
        if (isset(self::ACCESS_TYPE_ALIASES[$lower])) {
            return self::ACCESS_TYPE_ALIASES[$lower];
        }
        $upper = strtoupper($accessType);
        if (in_array($upper, [self::ACCESS_OPEN_STACK, self::ACCESS_STORAGE, self::ACCESS_REFERENCE], true)) {
            return $upper;
        }
        return null;
    }
}
