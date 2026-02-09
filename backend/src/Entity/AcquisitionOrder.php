<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\\Repository\\AcquisitionOrderRepository')]
#[ORM\Table(name: 'acquisition_order')]
#[ORM\Index(columns: ['status'], name: 'idx_order_status')]
#[ORM\Index(columns: ['supplier_id'], name: 'idx_order_supplier')]
#[ORM\Index(columns: ['created_at'], name: 'idx_order_created')]
class AcquisitionOrder
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_ORDERED = 'ORDERED';
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['acquisition:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['acquisition:read'])]
    private Supplier $supplier;

    #[ORM\ManyToOne(targetEntity: AcquisitionBudget::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['acquisition:read'])]
    private ?AcquisitionBudget $budget = null;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    #[Groups(['acquisition:read'])]
    private ?string $referenceNumber = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['acquisition:read'])]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['acquisition:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['acquisition:read'])]
    private ?array $items = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['acquisition:read'])]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['acquisition:read'])]
    private string $currency = 'PLN';

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['acquisition:read'])]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['acquisition:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['acquisition:read'])]
    private ?\DateTimeImmutable $orderedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['acquisition:read'])]
    private ?\DateTimeImmutable $expectedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['acquisition:read'])]
    private ?\DateTimeImmutable $receivedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

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

    public function getSupplier(): Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(Supplier $supplier): self
    {
        $this->supplier = $supplier;
        $this->touch();
        return $this;
    }

    public function getBudget(): ?AcquisitionBudget
    {
        return $this->budget;
    }

    public function setBudget(?AcquisitionBudget $budget): self
    {
        $this->budget = $budget;
        $this->touch();
        return $this;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(?string $referenceNumber): self
    {
        $this->referenceNumber = $referenceNumber !== null ? trim($referenceNumber) : null;
        $this->touch();
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);
        $this->touch();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->touch();
        return $this;
    }

    public function getItems(): ?array
    {
        return $this->items;
    }

    public function setItems(?array $items): self
    {
        $this->items = $items;
        $this->touch();
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $this->normalizeMoney($totalAmount);
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
            throw new \InvalidArgumentException('Currency must be 3 letters');
        }
        $this->currency = $currency;
        $this->touch();
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $allowed = [self::STATUS_DRAFT, self::STATUS_SUBMITTED, self::STATUS_ORDERED, self::STATUS_RECEIVED, self::STATUS_CANCELLED];
        $status = strtoupper(trim($status));
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid acquisition order status: ' . $status);
        }
        $this->status = $status;
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

    public function getOrderedAt(): ?\DateTimeImmutable
    {
        return $this->orderedAt;
    }

    public function setOrderedAt(?\DateTimeImmutable $orderedAt): self
    {
        $this->orderedAt = $orderedAt;
        $this->touch();
        return $this;
    }

    public function getExpectedAt(): ?\DateTimeImmutable
    {
        return $this->expectedAt;
    }

    public function setExpectedAt(?\DateTimeImmutable $expectedAt): self
    {
        $this->expectedAt = $expectedAt;
        $this->touch();
        return $this;
    }

    public function getReceivedAt(): ?\DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(?\DateTimeImmutable $receivedAt): self
    {
        $this->receivedAt = $receivedAt;
        $this->touch();
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): self
    {
        $this->cancelledAt = $cancelledAt;
        $this->touch();
        return $this;
    }

    public function markSubmitted(): self
    {
        return $this->setStatus(self::STATUS_SUBMITTED);
    }

    public function markOrdered(?\DateTimeImmutable $orderedAt = null): self
    {
        $this->setStatus(self::STATUS_ORDERED);
        $this->orderedAt = $orderedAt ?? new \DateTimeImmutable();
        return $this;
    }

    public function markReceived(?\DateTimeImmutable $receivedAt = null): self
    {
        $this->setStatus(self::STATUS_RECEIVED);
        $this->receivedAt = $receivedAt ?? new \DateTimeImmutable();
        return $this;
    }

    public function cancel(?\DateTimeImmutable $cancelledAt = null): self
    {
        $this->setStatus(self::STATUS_CANCELLED);
        $this->cancelledAt = $cancelledAt ?? new \DateTimeImmutable();
        return $this;
    }

    private function normalizeMoney(string $value): string
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException('Amount must be numeric');
        }
        return number_format((float) $value, 2, '.', '');
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
