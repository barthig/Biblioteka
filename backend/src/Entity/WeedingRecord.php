<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\\Repository\\WeedingRecordRepository')]
#[ORM\Table(name: 'weeding_record')]
#[ORM\Index(columns: ['book_id'], name: 'idx_weeding_book')]
#[ORM\Index(columns: ['action'], name: 'idx_weeding_action')]
class WeedingRecord
{
    public const ACTION_DISCARD = 'DISCARD';
    public const ACTION_DONATE = 'DONATE';
    public const ACTION_SELL = 'SELL';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['weeding:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['weeding:read'])]
    private Book $book;

    #[ORM\ManyToOne(targetEntity: BookCopy::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['weeding:read'])]
    private ?BookCopy $bookCopy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['weeding:read'])]
    private ?User $processedBy = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['weeding:read'])]
    private string $reason;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['weeding:read'])]
    private string $action = self::ACTION_DISCARD;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    #[Groups(['weeding:read'])]
    private ?string $conditionState = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['weeding:read'])]
    private \DateTimeImmutable $removedAt;

    public function __construct()
    {
        $this->removedAt = new \DateTimeImmutable();
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

    public function getProcessedBy(): ?User
    {
        return $this->processedBy;
    }

    public function setProcessedBy(?User $processedBy): self
    {
        $this->processedBy = $processedBy;
        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = trim($reason);
        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $action = strtoupper(trim($action));
        if (!in_array($action, [self::ACTION_DISCARD, self::ACTION_DONATE, self::ACTION_SELL], true)) {
            throw new \InvalidArgumentException('Invalid weeding action: ' . $action);
        }
        $this->action = $action;
        return $this;
    }

    public function getConditionState(): ?string
    {
        return $this->conditionState;
    }

    public function setConditionState(?string $conditionState): self
    {
        $this->conditionState = $conditionState !== null ? trim($conditionState) : null;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getRemovedAt(): \DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function setRemovedAt(\DateTimeImmutable $removedAt): self
    {
        $this->removedAt = $removedAt;
        return $this;
    }
}
