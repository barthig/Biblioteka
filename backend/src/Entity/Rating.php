<?php
namespace App\Entity;

use App\Repository\RatingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RatingRepository::class)]
#[ORM\Table(name: 'rating')]
#[ORM\UniqueConstraint(name: 'user_book_unique', columns: ['user_id', 'book_id'])]
class Rating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Book $book;

    #[ORM\Column(type: 'smallint')]
    private int $rating; // 1-5

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $review = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    
    public function getBook(): Book { return $this->book; }
    public function setBook(Book $book): self { $this->book = $book; return $this; }
    
    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): self 
    { 
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        $this->rating = $rating; 
        $this->updatedAt = new \DateTimeImmutable();
        return $this; 
    }
    
    public function getReview(): ?string { return $this->review; }
    public function setReview(?string $review): self 
    { 
        $this->review = $review; 
        $this->updatedAt = new \DateTimeImmutable();
        return $this; 
    }
    
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
}
