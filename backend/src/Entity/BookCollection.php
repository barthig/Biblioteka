<?php
namespace App\Entity;

use App\Repository\CollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectionRepository::class)]
#[ORM\Table(name: 'book_collection')]
class BookCollection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $curatedBy;

    #[ORM\ManyToMany(targetEntity: Book::class)]
    #[ORM\JoinTable(name: 'collection_books')]
    private DoctrineCollection $books;

    #[ORM\Column(type: 'boolean')]
    private bool $featured = false;

    #[ORM\Column(type: 'integer')]
    private int $displayOrder = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->books = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    
    public function getCuratedBy(): User { return $this->curatedBy; }
    public function setCuratedBy(User $user): self { $this->curatedBy = $user; return $this; }
    
    public function getBooks(): DoctrineCollection { return $this->books; }
    public function addBook(Book $book): self 
    {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }
    public function removeBook(Book $book): self 
    {
        $this->books->removeElement($book);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
    
    public function isFeatured(): bool { return $this->featured; }
    public function setFeatured(bool $featured): self { $this->featured = $featured; return $this; }
    
    public function getDisplayOrder(): int { return $this->displayOrder; }
    public function setDisplayOrder(int $order): self { $this->displayOrder = $order; return $this; }
    
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
}
