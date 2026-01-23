<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity]
class Book
{
    public const AGE_GROUP_TODDLERS = '0-2';
    public const AGE_GROUP_PRESCHOOL = '3-6';
    public const AGE_GROUP_EARLY_SCHOOL = '7-9';
    public const AGE_GROUP_MIDDLE_GRADE = '10-12';
    public const AGE_GROUP_YA_EARLY = '13-15';
    public const AGE_GROUP_YA_LATE = '16+';

    private const AGE_GROUP_DEFINITIONS = [
        self::AGE_GROUP_TODDLERS => [
            'label' => '0-2 lata',
            'description' => 'Niemowlęta i maluchy – książeczki sensoryczne, pierwsze słowa.'
        ],
        self::AGE_GROUP_PRESCHOOL => [
            'label' => '3-6 lat',
            'description' => 'Przedszkolaki – proste historie, intensywne ilustracje.'
        ],
        self::AGE_GROUP_EARLY_SCHOOL => [
            'label' => '7-9 lat',
            'description' => 'Wczesnoszkolne – pierwsze samodzielne czytanki i przygody.'
        ],
        self::AGE_GROUP_MIDDLE_GRADE => [
            'label' => '10-12 lat',
            'description' => 'Middle Grade – rozbudowane fabuły, bohaterowie w wieku czytelników.'
        ],
        self::AGE_GROUP_YA_EARLY => [
            'label' => '13-15 lat',
            'description' => 'Młodsze YA – odkrywanie tożsamości, relacje rówieśnicze.'
        ],
        self::AGE_GROUP_YA_LATE => [
            'label' => '16+ lat',
            'description' => 'Starsze YA / New Adult – tematy dojrzewania i pierwszych wyborów życiowych.'
        ],
    ];

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

    #[ORM\Column(type: 'vector', nullable: true, options: ['dimensions' => 1536], columnDefinition: 'vector(1536)')]
    private ?array $embedding = null;

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

    #[ORM\Column(type: 'string', length: 24, nullable: true)]
    #[Groups(['book:read', 'reservation:read'])]
    private ?string $targetAgeGroup = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['book:read', 'reservation:read'])]
    private \DateTimeInterface $createdAt;

    #[Groups(['book:read'])]
    private bool $isFavorite = false;

    #[Groups(['book:read'])]
    private ?float $averageRating = null;

    #[Groups(['book:read'])]
    private ?int $ratingCount = null;

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

    public function recalculateInventoryCounters(): ?self
    {
        $total = 0;
        $available = 0;
        $storageAvailable = 0;
        $openAvailable = 0;
        
        // Access the collection to trigger lazy loading if needed
        $inventory = $this->inventory;
        
        foreach ($inventory as $copy) {
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

    public function getEmbedding(): ?array
    {
        return $this->embedding;
    }

    public function setEmbedding(?array $embedding): self
    {
        $this->embedding = $embedding;

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

    public function getTargetAgeGroup(): ?string
    {
        return $this->targetAgeGroup;
    }

    public function setTargetAgeGroup(?string $ageGroup): self
    {
        if ($ageGroup !== null) {
            $ageGroup = trim($ageGroup);
            if ($ageGroup === '') {
                $ageGroup = null;
            }
        }

        if ($ageGroup !== null && !self::isValidAgeGroup($ageGroup)) {
            throw new \InvalidArgumentException(sprintf('Invalid age group "%s" provided for book.', $ageGroup));
        }

        $this->targetAgeGroup = $ageGroup;

        return $this;
    }

    #[Groups(['book:read', 'reservation:read'])]
    public function getTargetAgeGroupLabel(): ?string
    {
        if ($this->targetAgeGroup === null) {
            return null;
        }

        $definitions = self::getAgeGroupDefinitions();

        return $definitions[$this->targetAgeGroup]['label'] ?? $this->targetAgeGroup;
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

    public function getAverageRating(): ?float
    {
        return $this->averageRating;
    }

    public function setAverageRating(?float $averageRating): self
    {
        $this->averageRating = $averageRating;

        return $this;
    }

    public function getRatingCount(): ?int
    {
        return $this->ratingCount;
    }

    public function setRatingCount(?int $ratingCount): self
    {
        $this->ratingCount = $ratingCount;

        return $this;
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function getAgeGroupDefinitions(): array
    {
        return self::AGE_GROUP_DEFINITIONS;
    }

    public static function isValidAgeGroup(string $ageGroup): bool
    {
        return isset(self::AGE_GROUP_DEFINITIONS[$ageGroup]);
    }

    #[Groups(['book:read'])]
    public function getCoverUrl(): ?string
    {
        if ($this->id === null) {
            return null;
        }
        return '/api/books/' . $this->id . '/cover';
    }
}
