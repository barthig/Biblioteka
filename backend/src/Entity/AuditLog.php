<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\Repository\AuditLogRepository')]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(columns: ['entity_type', 'entity_id'], name: 'idx_audit_entity')]
#[ORM\Index(columns: ['action'], name: 'idx_audit_action')]
#[ORM\Index(columns: ['user_id'], name: 'idx_audit_user')]
#[ORM\Index(columns: ['created_at'], name: 'idx_audit_created')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['audit:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['audit:read'])]
    private string $entityType;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['audit:read'])]
    private ?int $entityId = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['audit:read'])]
    private string $action; // CREATE, UPDATE, DELETE, LOGIN, LOGOUT, etc.

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['audit:read'])]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    #[Groups(['audit:read'])]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['audit:read'])]
    private ?array $oldValues = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['audit:read'])]
    private ?array $newValues = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['audit:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['audit:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): self
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getOldValues(): ?array
    {
        return $this->oldValues;
    }

    public function setOldValues(?array $oldValues): self
    {
        $this->oldValues = $oldValues;
        return $this;
    }

    public function getNewValues(): ?array
    {
        return $this->newValues;
    }

    public function setNewValues(?array $newValues): self
    {
        $this->newValues = $newValues;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
