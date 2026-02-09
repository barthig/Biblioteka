<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\\Repository\\AcquisitionExpenseRepository')]
class AcquisitionExpense
{
    public const TYPE_ORDER = 'ORDER';
    public const TYPE_MISC = 'MISC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['budget:read', 'acquisition:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AcquisitionBudget::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['budget:read'])]
    private AcquisitionBudget $budget;

    #[ORM\ManyToOne(targetEntity: AcquisitionOrder::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['acquisition:read'])]
    private ?AcquisitionOrder $order = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['budget:read', 'acquisition:read'])]
    private string $amount = '0.00';

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['budget:read', 'acquisition:read'])]
    private string $currency = 'PLN';

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['budget:read', 'acquisition:read'])]
    private string $description;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['budget:read'])]
    private string $type = self::TYPE_MISC;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['budget:read', 'acquisition:read'])]
    private \DateTimeImmutable $postedAt;

    public function __construct()
    {
        $this->postedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBudget(): AcquisitionBudget
    {
        return $this->budget;
    }

    public function setBudget(AcquisitionBudget $budget): self
    {
        $this->budget = $budget;
        return $this;
    }

    public function getOrder(): ?AcquisitionOrder
    {
        return $this->order;
    }

    public function setOrder(?AcquisitionOrder $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Amount must be numeric');
        }
        $this->amount = number_format((float) $amount, 2, '.', '');
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
            throw new \InvalidArgumentException('Currency must be 3 letters');
        }
        $this->currency = $currency;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $type = strtoupper(trim($type));
        if (!in_array($type, [self::TYPE_ORDER, self::TYPE_MISC], true)) {
            throw new \InvalidArgumentException('Invalid expense type: ' . $type);
        }
        $this->type = $type;
        return $this;
    }

    public function getPostedAt(): \DateTimeImmutable
    {
        return $this->postedAt;
    }

    public function setPostedAt(\DateTimeImmutable $postedAt): self
    {
        $this->postedAt = $postedAt;
        return $this;
    }
}
