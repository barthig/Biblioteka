<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\\Repository\\AcquisitionBudgetRepository')]
class AcquisitionBudget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['budget:read', 'acquisition:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 160)]
    #[Groups(['budget:read', 'acquisition:read'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 9)]
    #[Groups(['budget:read'])]
    private string $fiscalYear;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['budget:read'])]
    private string $allocatedAmount = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['budget:read'])]
    private string $spentAmount = '0.00';

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['budget:read'])]
    private string $currency = 'PLN';

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['budget:read'])]
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

    public function getFiscalYear(): string
    {
        return $this->fiscalYear;
    }

    public function setFiscalYear(string $fiscalYear): self
    {
        $this->fiscalYear = trim($fiscalYear);
        $this->touch();
        return $this;
    }

    public function getAllocatedAmount(): string
    {
        return $this->allocatedAmount;
    }

    public function setAllocatedAmount(string $allocatedAmount): self
    {
        $this->allocatedAmount = $this->normalizeMoney($allocatedAmount);
        $this->touch();
        return $this;
    }

    public function getSpentAmount(): string
    {
        return $this->spentAmount;
    }

    public function setSpentAmount(string $spentAmount): self
    {
        $this->spentAmount = $this->normalizeMoney($spentAmount);
        $this->touch();
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $currency = strtoupper(trim($currency));
        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Currency must be a 3-letter ISO code');
        }
        $this->currency = $currency;
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

    public function registerExpense(string $amount): self
    {
        return $this->adjustSpentBy($amount);
    }

    public function adjustSpentBy(string $delta): self
    {
        if (!is_numeric($delta)) {
            throw new \InvalidArgumentException('Amount must be numeric');
        }

        $current = (float) $this->spentAmount;
        $change = (float) $this->normalizeMoney($delta);
        $change = (float) $delta < 0 ? -$change : $change;
        $this->spentAmount = number_format(max(0.0, $current + $change), 2, '.', '');
        $this->touch();
        return $this;
    }

    public function remainingAmount(): string
    {
        $remaining = (float) $this->allocatedAmount - (float) $this->spentAmount;
        return number_format($remaining, 2, '.', '');
    }

    private function normalizeMoney(string $value): string
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException('Amount must be numeric');
        }
        return number_format(abs((float) $value), 2, '.', '');
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
