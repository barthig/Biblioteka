<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255)]
    private string $author;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $isbn = null;

    #[ORM\Column(type: 'integer')]
    private int $copies = 1;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $t): self { $this->title = $t; return $this; }
    public function getAuthor(): string { return $this->author; }
    public function setAuthor(string $a): self { $this->author = $a; return $this; }
    public function getIsbn(): ?string { return $this->isbn; }
    public function setIsbn(?string $i): self { $this->isbn = $i; return $this; }
    public function getCopies(): int { return $this->copies; }
    public function setCopies(int $c): self { $this->copies = $c; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
