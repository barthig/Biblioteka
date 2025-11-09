<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['book:read', 'loan:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['book:read', 'loan:read'])]
    private string $title;

    #[ORM\ManyToOne(targetEntity: Author::class, inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['book:read', 'loan:read'])]
    private Author $author;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'books')]
    #[ORM\JoinTable(name: 'book_category')]
    #[Groups(['book:read'])]
    private Collection $categories;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['book:read'])]
    private ?string $isbn = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['book:read', 'loan:read'])]
    private int $copies = 1;

    #[ORM\Column(type: 'integer')]
    #[Groups(['book:read'])]
    #[SerializedName('totalCopies')]
    private int $totalCopies = 1;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['book:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['book:read'])]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): self
    {
        if ($author === null) {
            throw new \InvalidArgumentException('Author cannot be null for a book');
        }

        if (isset($this->author) && $this->author === $author) {
            return $this;
        }

        if (isset($this->author)) {
            $this->author->getBooks()->removeElement($this);
        }

        $this->author = $author;

        if (!$author->getBooks()->contains($this)) {
            $author->getBooks()->add($this);
        }

        return $this;
    }

    /** @return Collection<int, Category> */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            if (!$category->getBooks()->contains($this)) {
                $category->getBooks()->add($this);
            }
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        if ($this->categories->removeElement($category)) {
            $category->getBooks()->removeElement($this);
        }

        return $this;
    }

    public function clearCategories(): self
    {
        foreach ($this->categories as $category) {
            $category->getBooks()->removeElement($this);
        }

        $this->categories->clear();

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): self
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getCopies(): int
    {
        return $this->copies;
    }

    public function setCopies(int $copies): self
    {
        $copies = max(0, $copies);
        $this->copies = $this->totalCopies > 0 ? min($copies, $this->totalCopies) : $copies;

        return $this;
    }

    public function getTotalCopies(): int
    {
        return $this->totalCopies;
    }

    public function setTotalCopies(int $totalCopies): self
    {
        $totalCopies = max(0, $totalCopies);
        $this->totalCopies = $totalCopies;
        if ($this->copies > $totalCopies && $totalCopies > 0) {
            $this->copies = $totalCopies;
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
