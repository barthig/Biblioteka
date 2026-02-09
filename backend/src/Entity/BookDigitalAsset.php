<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: 'App\\Repository\\BookDigitalAssetRepository')]
class BookDigitalAsset
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['asset:read', 'book:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'digitalAssets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Book $book;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['asset:read', 'book:read'])]
    private string $label;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['asset:read'])]
    private string $originalFilename;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['asset:read'])]
    private string $mimeType;

    #[ORM\Column(type: 'integer')]
    #[Groups(['asset:read'])]
    private int $size;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $storageName;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['asset:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = trim($label) !== '' ? $label : 'Plik cyfrowy';
        return $this;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): self
    {
        $this->originalFilename = $originalFilename;
        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = max(0, $size);
        return $this;
    }

    public function getStorageName(): string
    {
        return $this->storageName;
    }

    public function setStorageName(string $storageName): self
    {
        $this->storageName = $storageName;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
