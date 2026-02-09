<?php
declare(strict_types=1);
namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Trait dla soft delete - zamiast usuwania fizycznego, ustawiamy deletedAt
 */
trait SoftDeletableTrait
{
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['admin:read'])]
    private ?\DateTimeImmutable $deletedAt = null;

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    /**
     * Soft delete - oznacza encję jako usuniętą
     */
    public function softDelete(): self
    {
        $this->deletedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Przywraca usuniętą encję
     */
    public function restore(): self
    {
        $this->deletedAt = null;
        return $this;
    }
}
