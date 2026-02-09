<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\\Repository\\LoanRepository')]
#[ORM\Table(name: 'loan')]
#[ORM\Index(columns: ['user_id'], name: 'idx_loan_user')]
#[ORM\Index(columns: ['book_id'], name: 'idx_loan_book')]
#[ORM\Index(columns: ['due_at'], name: 'idx_loan_due')]
#[ORM\Index(columns: ['returned_at'], name: 'idx_loan_returned')]
#[ORM\HasLifecycleCallbacks]
class Loan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['loan:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['loan:read'])]
    private Book $book;

    #[ORM\ManyToOne(targetEntity: BookCopy::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['loan:read'])]
    private ?BookCopy $bookCopy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['loan:read'])]
    private User $user;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['loan:read'])]
    private \DateTimeImmutable $borrowedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['loan:read'])]
    private \DateTimeImmutable $dueAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['loan:read'])]
    private ?\DateTimeImmutable $returnedAt = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['loan:read'])]
    private int $extensionsCount = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['loan:read'])]
    private ?\DateTimeImmutable $lastExtendedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->borrowedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function getId(): ?int { return $this->id; }
    public function getBook(): Book { return $this->book; }
    public function setBook(Book $b): self { $this->book = $b; return $this; }
    public function getBookCopy(): ?BookCopy { return $this->bookCopy; }
    public function setBookCopy(?BookCopy $copy): self { $this->bookCopy = $copy; return $this; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $u): self { $this->user = $u; return $this; }
    public function getBorrowedAt(): \DateTimeImmutable { return $this->borrowedAt; }
    public function getDueAt(): \DateTimeImmutable { return $this->dueAt; }
    public function setDueAt(\DateTimeImmutable $d): self { $this->dueAt = $d; return $this; }
    public function getReturnedAt(): ?\DateTimeImmutable { return $this->returnedAt; }
    public function setReturnedAt(?\DateTimeImmutable $r): self { $this->returnedAt = $r; return $this; }

    public function getExtensionsCount(): int { return $this->extensionsCount; }
    public function setExtensionsCount(int $count): self { $this->extensionsCount = max(0, $count); return $this; }
    public function incrementExtensions(): self { $this->extensionsCount = max(0, $this->extensionsCount) + 1; return $this; }
    public function getLastExtendedAt(): ?\DateTimeImmutable { return $this->lastExtendedAt; }
    public function setLastExtendedAt(?\DateTimeImmutable $moment): self { $this->lastExtendedAt = $moment; return $this; }
}
