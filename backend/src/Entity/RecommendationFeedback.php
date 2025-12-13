<?php
namespace App\Entity;

use App\Repository\RecommendationFeedbackRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecommendationFeedbackRepository::class)]
#[ORM\Table(name: 'recommendation_feedback')]
#[ORM\UniqueConstraint(name: 'user_book_feedback_unique', columns: ['user_id', 'book_id'])]
class RecommendationFeedback
{
    public const TYPE_DISMISS = 'dismiss';
    public const TYPE_INTERESTED = 'interested';

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

    #[ORM\Column(type: 'string', length: 20)]
    private string $feedbackType;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    
    public function getBook(): Book { return $this->book; }
    public function setBook(Book $book): self { $this->book = $book; return $this; }
    
    public function getFeedbackType(): string { return $this->feedbackType; }
    public function setFeedbackType(string $type): self 
    { 
        if (!in_array($type, [self::TYPE_DISMISS, self::TYPE_INTERESTED])) {
            throw new \InvalidArgumentException('Invalid feedback type');
        }
        $this->feedbackType = $type; 
        return $this; 
    }
    
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
