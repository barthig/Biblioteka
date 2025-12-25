<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: \App\Repository\AnnouncementRepository::class)]
#[ORM\Table(name: 'announcement')]
class Announcement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['announcement:read', 'announcement:list'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['announcement:read', 'announcement:list', 'announcement:write'])]
    private string $title;

    #[ORM\Column(type: 'text')]
    #[Groups(['announcement:read', 'announcement:list', 'announcement:write'])]
    private string $content;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['announcement:read', 'announcement:list', 'announcement:write'])]
    private ?string $location = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['announcement:read', 'announcement:list', 'announcement:write'])]
    private string $type = 'info'; // info, warning, urgent, maintenance

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['announcement:read', 'announcement:list'])]
    private string $status = 'draft'; // draft, published, archived

    #[ORM\Column(type: 'boolean')]
    #[Groups(['announcement:read', 'announcement:list', 'announcement:write'])]
    private bool $isPinned = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['announcement:read', 'announcement:list', 'announcement:write'])]
    private bool $showOnHomepage = true;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['announcement:read'])]
    private User $createdBy;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['announcement:read', 'announcement:list'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['announcement:read'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['announcement:read', 'announcement:write'])]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['announcement:read', 'announcement:write'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['announcement:read', 'announcement:list', 'announcement:write'])]
    private ?\DateTimeImmutable $eventAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['announcement:read', 'announcement:write'])]
    private ?array $targetAudience = null; // ['all'], ['students'], ['librarians'], etc.

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned): self
    {
        $this->isPinned = $isPinned;
        return $this;
    }

    public function isShowOnHomepage(): bool
    {
        return $this->showOnHomepage;
    }

    public function setShowOnHomepage(bool $showOnHomepage): self
    {
        $this->showOnHomepage = $showOnHomepage;
        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getEventAt(): ?\DateTimeImmutable
    {
        return $this->eventAt;
    }

    public function setEventAt(?\DateTimeImmutable $eventAt): self
    {
        $this->eventAt = $eventAt;
        return $this;
    }

    public function getTargetAudience(): ?array
    {
        return $this->targetAudience;
    }

    public function setTargetAudience(?array $targetAudience): self
    {
        $this->targetAudience = $targetAudience;
        return $this;
    }

    public function publish(): self
    {
        $this->status = 'published';
        if (!$this->publishedAt) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function archive(): self
    {
        $this->status = 'archived';
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isActive(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        $now = new \DateTimeImmutable();
        
        if ($this->publishedAt && $this->publishedAt > $now) {
            return false;
        }

        if ($this->expiresAt && $this->expiresAt < $now) {
            return false;
        }

        return true;
    }

    public function isVisibleForUser(?User $user): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if (!$this->targetAudience || in_array('all', $this->targetAudience, true)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $userRoles = $user->getRoles();
        
        foreach ($this->targetAudience as $audience) {
            if (in_array('ROLE_LIBRARIAN', $userRoles, true) && $audience === 'librarians') {
                return true;
            }
            if (in_array('ROLE_USER', $userRoles, true) && $audience === 'users') {
                return true;
            }
        }

        return false;
    }
}
