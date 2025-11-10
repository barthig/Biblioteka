<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\\Repository\\OrderRequestRepository')]
#[ORM\Table(name: 'order_request')]
class OrderRequest
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_READY = 'READY';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_COLLECTED = 'COLLECTED';
    public const STATUS_EXPIRED = 'EXPIRED';

    public const PICKUP_STORAGE_DESK = 'STORAGE_DESK';
    public const PICKUP_OPEN_SHELF = 'OPEN_SHELF';
    public const PICKUP_TYPES = [self::PICKUP_STORAGE_DESK, self::PICKUP_OPEN_SHELF];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['order:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['order:read'])]
    private Book $book;

    #[ORM\ManyToOne(targetEntity: BookCopy::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['order:read'])]
    private ?BookCopy $bookCopy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['order:read'])]
    private User $user;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['order:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'string', length: 30)]
    #[Groups(['order:read'])]
    private string $pickupType = self::PICKUP_STORAGE_DESK;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['order:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $pickupDeadline = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $collectedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $expiredAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getBookCopy(): ?BookCopy
    {
        return $this->bookCopy;
    }

    public function setBookCopy(?BookCopy $bookCopy): self
    {
        $this->bookCopy = $bookCopy;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_READY, self::STATUS_CANCELLED, self::STATUS_COLLECTED, self::STATUS_EXPIRED], true)) {
            throw new \InvalidArgumentException('Invalid order status: ' . $status);
        }
        $this->status = $status;
        return $this;
    }

    public function markReady(?\DateTimeImmutable $deadline = null): self
    {
        $this->status = self::STATUS_READY;
        $this->pickupDeadline = $deadline;
        return $this;
    }

    public function cancel(): self
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelledAt = new \DateTimeImmutable();
        return $this;
    }

    public function markCollected(): self
    {
        $this->status = self::STATUS_COLLECTED;
        $this->collectedAt = new \DateTimeImmutable();
        return $this;
    }

    public function expire(): self
    {
        $this->status = self::STATUS_EXPIRED;
        $this->expiredAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPickupType(): ?string
    {
        return $this->pickupType;
    }

    public function setPickupType(?string $pickupType): self
    {
        if ($pickupType === null) {
            $this->pickupType = self::PICKUP_STORAGE_DESK;
            return $this;
        }

        $normalized = strtoupper(trim($pickupType));
        if (!in_array($normalized, self::PICKUP_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid pickup type: ' . $pickupType);
        }

        $this->pickupType = $normalized;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPickupDeadline(): ?\DateTimeImmutable
    {
        return $this->pickupDeadline;
    }

    public function setPickupDeadline(?\DateTimeImmutable $pickupDeadline): self
    {
        $this->pickupDeadline = $pickupDeadline;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getCollectedAt(): ?\DateTimeImmutable
    {
        return $this->collectedAt;
    }

    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->expiredAt;
    }
}
