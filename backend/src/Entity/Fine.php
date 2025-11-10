<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\\Repository\\FineRepository')]
class Fine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['fine:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Loan::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['fine:read'])]
    private Loan $loan;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    #[Groups(['fine:read'])]
    private string $amount = '0.00';

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['fine:read'])]
    private string $currency = 'PLN';

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['fine:read'])]
    private string $reason;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['fine:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['fine:read'])]
    private ?\DateTimeImmutable $paidAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLoan(): Loan
    {
        return $this->loan;
    }

    public function setLoan(Loan $loan): self
    {
        $this->loan = $loan;
        return $this;
    }

    public function getAmount(): string
    {
        return number_format((float) $this->amount, 2, '.', '');
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
        $this->currency = strtoupper($currency);
        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function markAsPaid(): self
    {
        $this->paidAt = new \DateTimeImmutable();
        return $this;
    }
}
