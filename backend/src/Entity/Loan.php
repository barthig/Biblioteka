<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
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

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['loan:read'])]
    private User $user;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['loan:read'])]
    private \DateTimeInterface $borrowedAt;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['loan:read'])]
    private \DateTimeInterface $dueAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['loan:read'])]
    private ?\DateTimeInterface $returnedAt = null;

    public function __construct()
    {
        $this->borrowedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getBook(): Book { return $this->book; }
    public function setBook(Book $b): self { $this->book = $b; return $this; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $u): self { $this->user = $u; return $this; }
    public function getBorrowedAt(): \DateTimeInterface { return $this->borrowedAt; }
    public function getDueAt(): \DateTimeInterface { return $this->dueAt; }
    public function setDueAt(\DateTimeInterface $d): self { $this->dueAt = $d; return $this; }
    public function getReturnedAt(): ?\DateTimeInterface { return $this->returnedAt; }
    public function setReturnedAt(?\DateTimeInterface $r): self { $this->returnedAt = $r; return $this; }
}
