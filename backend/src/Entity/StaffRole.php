<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\\Repository\\StaffRoleRepository')]
#[ORM\Table(name: 'staff_role')]
class StaffRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 120, unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', length: 120, unique: true)]
    private string $roleKey;

    #[ORM\Column(type: 'json')]
    private array $modules = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        $this->touch();
        return $this;
    }

    public function getRoleKey(): string
    {
        return $this->roleKey;
    }

    public function setRoleKey(string $roleKey): self
    {
        $normalized = strtoupper(trim($roleKey));
        if (!str_starts_with($normalized, 'ROLE_')) {
            $normalized = 'ROLE_' . $normalized;
        }
        $this->roleKey = $normalized;
        $this->touch();
        return $this;
    }

    public function getModules(): array
    {
        return $this->modules;
    }

    public function setModules(array $modules): self
    {
        $normalized = array_values(array_unique(array_map(static function ($module): string {
            return strtolower(trim((string) $module));
        }, $modules)));
        $this->modules = $normalized;
        $this->touch();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description !== null ? trim($description) : null;
        $this->touch();
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

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
