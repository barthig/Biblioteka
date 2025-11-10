<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
class User
{
    public const GROUP_STANDARD = 'standard';
    public const GROUP_STUDENT = 'student';
    public const GROUP_RESEARCHER = 'pracownik_naukowy';
    public const GROUP_CHILD = 'dziecko';

    public const GROUP_LIMITS = [
        self::GROUP_STANDARD => 5,
        self::GROUP_STUDENT => 5,
        self::GROUP_RESEARCHER => 10,
        self::GROUP_CHILD => 3,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['loan:read', 'reservation:read', 'order:read', 'review:read', 'favorite:read', 'weeding:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['loan:read', 'reservation:read', 'order:read', 'review:read', 'favorite:read', 'weeding:read'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['loan:read', 'reservation:read', 'order:read', 'review:read', 'favorite:read', 'weeding:read'])]
    private string $name;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string', length: 255)]
    #[Ignore]
    private string $password;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    #[Groups(['loan:read'])]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $addressLine = null;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 12, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['loan:read'])]
    private bool $blocked = false;

    #[ORM\Column(type: 'string', length: 64, options: ['default' => self::GROUP_STANDARD])]
    #[Groups(['loan:read'])]
    private string $membershipGroup = self::GROUP_STANDARD;

    #[ORM\Column(type: 'integer', options: ['default' => 5])]
    #[Groups(['loan:read'])]
    private int $loanLimit = 5;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $blockedReason = null;

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $e): self { $this->email = $e; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): self { $this->name = $n; return $this; }
    public function getRoles(): array { return $this->roles; }
    public function setRoles(array $r): self { $this->roles = $r; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $hashed): self { $this->password = $hashed; return $this; }

    public function getPhoneNumber(): ?string { return $this->phoneNumber; }
    public function setPhoneNumber(?string $phone): self { $this->phoneNumber = $phone; return $this; }
    public function getAddressLine(): ?string { return $this->addressLine; }
    public function setAddressLine(?string $address): self { $this->addressLine = $address; return $this; }
    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): self { $this->city = $city; return $this; }
    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(?string $postalCode): self { $this->postalCode = $postalCode; return $this; }

    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    public function block(?string $reason = null): self
    {
        $this->blocked = true;
        $this->blockedReason = $reason !== null ? $this->truncateReason($reason) : null;
        return $this;
    }

    public function unblock(): self
    {
        $this->blocked = false;
        $this->blockedReason = null;
        return $this;
    }

    public function getBlockedReason(): ?string
    {
        return $this->blockedReason;
    }

    public function getMembershipGroup(): string
    {
        return $this->membershipGroup;
    }

    public function setMembershipGroup(string $group): self
    {
        $normalized = self::normalizeGroup($group);
        if (!array_key_exists($normalized, self::GROUP_LIMITS)) {
            throw new \InvalidArgumentException('Unknown membership group: ' . $group);
        }

        $this->membershipGroup = $normalized;
        $this->applyDefaultLoanLimit();
        return $this;
    }

    public function getLoanLimit(): int
    {
        return $this->loanLimit;
    }

    public function setLoanLimit(int $loanLimit): self
    {
        $this->loanLimit = max(0, $loanLimit);
        return $this;
    }

    public function applyDefaultLoanLimit(): self
    {
        $this->loanLimit = self::GROUP_LIMITS[$this->membershipGroup] ?? $this->loanLimit;
        return $this;
    }

    public static function normalizeGroup(string $group): string
    {
        $normalized = strtolower(trim($group));
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized) ?? $normalized;
        $normalized = trim($normalized, '_');
        return $normalized !== '' ? $normalized : self::GROUP_STANDARD;
    }

    private function truncateReason(string $reason): string
    {
        $trimmed = trim($reason);
        if (strlen($trimmed) > 255) {
            $trimmed = substr($trimmed, 0, 255);
        }

        return $trimmed;
    }
}
