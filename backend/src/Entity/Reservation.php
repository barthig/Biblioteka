<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\\Repository\\ReservationRepository')]
class Reservation
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_FULFILLED = 'FULFILLED';
    public const STATUS_EXPIRED = 'EXPIRED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['reservation:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['reservation:read'])]
    private Book $book;

    #[ORM\ManyToOne(targetEntity: BookCopy::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['reservation:read'])]
    private ?BookCopy $bookCopy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['reservation:read'])]
    private User $user;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['reservation:read'])]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['reservation:read'])]
    private \DateTimeImmutable $reservedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['reservation:read'])]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['reservation:read'])]
    private ?\DateTimeImmutable $fulfilledAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['reservation:read'])]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['reservation:read'])]
    private ?\DateTimeImmutable $expiredAt = null;

    public function __construct()
    {
        $this->reservedAt = new \DateTimeImmutable();
        $this->expiresAt = $this->reservedAt->modify('+3 days');
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

    public function assignBookCopy(BookCopy $copy): self
    {
        $this->bookCopy = $copy;
        return $this;
    }

    public function clearBookCopy(): self
    {
        $this->bookCopy = null;
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
        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_CANCELLED, self::STATUS_FULFILLED, self::STATUS_EXPIRED], true)) {
            throw new \InvalidArgumentException('Invalid reservation status: ' . $status);
        }
        $this->status = $status;
        return $this;
    }

    public function getReservedAt(): \DateTimeImmutable
    {
        return $this->reservedAt;
    }

    public function setReservedAt(\DateTimeImmutable $reservedAt): self
    {
        $this->reservedAt = $reservedAt;
        return $this;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getFulfilledAt(): ?\DateTimeImmutable
    {
        return $this->fulfilledAt;
    }

    public function markFulfilled(): self
    {
        $this->status = self::STATUS_FULFILLED;
        $this->fulfilledAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function cancel(): self
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelledAt = new \DateTimeImmutable();
        return $this;
    }

    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function expire(): self
    {
        $this->status = self::STATUS_EXPIRED;
        $this->expiredAt = new \DateTimeImmutable();
        return $this;
    }
}
