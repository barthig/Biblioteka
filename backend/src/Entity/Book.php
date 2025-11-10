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
    #[Groups(['book:read', 'loan:read', 'reservation:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['book:read', 'loan:read', 'reservation:read'])]
    private string $title;

    #[ORM\ManyToOne(targetEntity: Author::class, inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['book:read', 'loan:read', 'reservation:read'])]
    private Author $author;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'books')]
    #[ORM\JoinTable(name: 'book_category')]
    #[Groups(['book:read', 'reservation:read'])]
    private Collection $categories;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['book:read', 'reservation:read'])]
    private ?string $isbn = null;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: BookCopy::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['inventory:read'])]
    private Collection $inventory;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: BookDigitalAsset::class, cascade: ['remove'], orphanRemoval: true)]
    #[Groups(['book:read'])]
    private Collection $digitalAssets;

    #[ORM\Column(type: 'integer')]
    #[Groups(['book:read', 'loan:read', 'reservation:read'])]
    private int $copies = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['book:read', 'reservation:read'])]
    #[SerializedName('totalCopies')]
    private int $totalCopies = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['book:read', 'reservation:read'])]
    private int $storageCopies = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['book:read', 'reservation:read'])]
    private int $openStackCopies = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['book:read', 'reservation:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    #[Groups(['book:read', 'loan:read', 'reservation:read'])]
    private ?string $publisher = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups(['book:read', 'loan:read', 'reservation:read'])]
    private ?int $publicationYear = null;

    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    #[Groups(['book:read', 'loan:read', 'reservation:read'])]
    private ?string $resourceType = null;

    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    #[Groups(['book:read', 'loan:read', 'reservation:read'])]
    private ?string $signature = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['book:read', 'reservation:read'])]
    private \DateTimeInterface $createdAt;

    #[Groups(['book:read'])]
    private bool $isFavorite = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->categories = new ArrayCollection();
        $this->inventory = new ArrayCollection();
        $this->digitalAssets = new ArrayCollection();
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

    /** @return Collection<int, BookCopy> */
    public function getInventory(): Collection
    {
        return $this->inventory;
    }

    public function addInventoryCopy(BookCopy $copy): self
    {
        if (!$this->inventory->contains($copy)) {
            $this->inventory->add($copy);
            $copy->setBook($this);
            $this->recalculateInventoryCounters();
        }

        return $this;
    }

    /** @return Collection<int, BookDigitalAsset> */
    public function getDigitalAssets(): Collection
    {
        return $this->digitalAssets;
    }

    public function addDigitalAsset(BookDigitalAsset $asset): self
    {
        if (!$this->digitalAssets->contains($asset)) {
            $this->digitalAssets->add($asset);
            $asset->setBook($this);
        }

        return $this;
    }

    public function removeDigitalAsset(BookDigitalAsset $asset): self
    {
        $this->digitalAssets->removeElement($asset);

        return $this;
    }

    public function removeInventoryCopy(BookCopy $copy): self
    {
        if ($this->inventory->removeElement($copy)) {
            $this->recalculateInventoryCounters();
        }

        return $this;
    }

    public function getCopies(): int
    {
        return $this->copies;
    }

    public function setCopies(int $copies): self
    {
        $this->copies = max(0, $copies);

        return $this;
    }

    public function getTotalCopies(): int
    {
        return $this->totalCopies;
    }

    public function setTotalCopies(int $totalCopies): self
    {
        $this->totalCopies = max(0, $totalCopies);

        return $this;
    }

    public function recalculateInventoryCounters(): self
    {
        $total = 0;
        $available = 0;
        $storageAvailable = 0;
        $openAvailable = 0;
        foreach ($this->inventory as $copy) {
            if ($copy->getStatus() === BookCopy::STATUS_WITHDRAWN) {
                continue;
            }

            ++$total;
            if ($copy->getStatus() === BookCopy::STATUS_AVAILABLE) {
                ++$available;
                if ($copy->getAccessType() === BookCopy::ACCESS_STORAGE) {
                    ++$storageAvailable;
                } elseif ($copy->getAccessType() === BookCopy::ACCESS_OPEN_STACK) {
                    ++$openAvailable;
                }
            }
        }

        $this->totalCopies = $total;
        $this->copies = $available;
        $this->storageCopies = $storageAvailable;
        $this->openStackCopies = $openAvailable;

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

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function setPublisher(?string $publisher): self
    {
        $this->publisher = $publisher !== null ? trim($publisher) : null;

        return $this;
    }

    public function getPublicationYear(): ?int
    {
        return $this->publicationYear;
    }

    public function setPublicationYear(?int $year): self
    {
        if ($year !== null) {
            $year = max(0, min(9999, $year));
        }
        $this->publicationYear = $year;

        return $this;
    }

    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    public function setResourceType(?string $resourceType): self
    {
        $this->resourceType = $resourceType !== null ? trim($resourceType) : null;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): self
    {
        $this->signature = $signature !== null ? trim($signature) : null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getStorageCopies(): int
    {
        return $this->storageCopies;
    }

    public function setStorageCopies(int $storageCopies): self
    {
        $this->storageCopies = max(0, $storageCopies);

        return $this;
    }

    public function getOpenStackCopies(): int
    {
        return $this->openStackCopies;
    }

    public function setOpenStackCopies(int $openStackCopies): self
    {
        $this->openStackCopies = max(0, $openStackCopies);

        return $this;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): self
    {
        $this->isFavorite = $isFavorite;

        return $this;
    }
}
