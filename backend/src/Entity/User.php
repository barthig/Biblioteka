<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
#[ORM\HasLifecycleCallbacks]
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
    #[Groups(['user:read', 'loan:read', 'reservation:read', 'order:read', 'review:read', 'favorite:read', 'weeding:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['user:read', 'loan:read', 'reservation:read', 'order:read', 'review:read', 'favorite:read', 'weeding:read'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['user:read', 'loan:read', 'reservation:read', 'order:read', 'review:read', 'favorite:read', 'weeding:read'])]
    private string $name;

    #[ORM\Column(type: 'json')]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column(type: 'string', length: 255)]
    #[Ignore]
    private string $password;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    #[Groups(['user:read', 'loan:read'])]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $addressLine = null;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 12, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $postalCode = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['loan:read'])]
    private bool $blocked = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $verified = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $pendingApproval = false;

    #[ORM\Column(type: 'string', length: 64, options: ['default' => self::GROUP_STANDARD])]
    #[Groups(['loan:read'])]
    private string $membershipGroup = self::GROUP_STANDARD;

    #[ORM\Column(type: 'integer', options: ['default' => 5])]
    #[Groups(['loan:read'])]
    private int $loanLimit = 5;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $blockedReason = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $privacyConsentAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $newsletterSubscribed = true;

    #[ORM\Column(type: 'string', length: 11, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $pesel = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $cardNumber = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cardExpiry = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $accountStatus = 'Aktywne';

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $keepHistory = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $emailLoans = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $emailReservations = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $emailFines = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $emailAnnouncements = false;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $preferredContact = 'email';

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $defaultBranch = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $theme = 'auto';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $preferredCategories = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $onboardingCompleted = false;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $fontSize = 'standard';

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    private ?string $language = 'pl';

    #[ORM\Column(type: 'string', length: 4, nullable: true)]
    private ?string $pin = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->applyDefaultLoanLimit();
        $this->newsletterSubscribed = true;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

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

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function markVerified(?\DateTimeImmutable $verifiedAt = null): self
    {
        $this->verified = true;
        $this->verifiedAt = $verifiedAt ?? new \DateTimeImmutable();
        return $this;
    }

    public function requireVerification(): self
    {
        $this->verified = false;
        $this->verifiedAt = null;
        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function isPendingApproval(): bool
    {
        return $this->pendingApproval;
    }

    public function setPendingApproval(bool $pendingApproval): self
    {
        $this->pendingApproval = $pendingApproval;
        return $this;
    }

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPrivacyConsentAt(): ?\DateTimeImmutable
    {
        return $this->privacyConsentAt;
    }

    public function recordPrivacyConsent(?\DateTimeImmutable $consentAt = null): self
    {
        $this->privacyConsentAt = $consentAt ?? new \DateTimeImmutable();
        return $this;
    }

    public function isNewsletterSubscribed(): bool
    {
        return $this->newsletterSubscribed;
    }

    public function setNewsletterSubscribed(bool $newsletterSubscribed): self
    {
        $this->newsletterSubscribed = $newsletterSubscribed;
        return $this;
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

    // New getters and setters
    public function getPesel(): ?string { return $this->pesel; }
    public function setPesel(?string $pesel): self { $this->pesel = $pesel; return $this; }

    public function getCardNumber(): ?string { return $this->cardNumber; }
    public function setCardNumber(?string $cardNumber): self { $this->cardNumber = $cardNumber; return $this; }

    public function getCardExpiry(): ?\DateTimeImmutable { return $this->cardExpiry; }
    public function setCardExpiry(?\DateTimeImmutable $cardExpiry): self { $this->cardExpiry = $cardExpiry; return $this; }

    public function getAccountStatus(): ?string { return $this->accountStatus; }
    public function setAccountStatus(?string $accountStatus): self { $this->accountStatus = $accountStatus; return $this; }

    public function getKeepHistory(): bool { return $this->keepHistory; }
    public function setKeepHistory(bool $keepHistory): self { $this->keepHistory = $keepHistory; return $this; }

    public function getEmailLoans(): bool { return $this->emailLoans; }
    public function setEmailLoans(bool $emailLoans): self { $this->emailLoans = $emailLoans; return $this; }

    public function getEmailReservations(): bool { return $this->emailReservations; }
    public function setEmailReservations(bool $emailReservations): self { $this->emailReservations = $emailReservations; return $this; }

    public function getEmailFines(): bool { return $this->emailFines; }
    public function setEmailFines(bool $emailFines): self { $this->emailFines = $emailFines; return $this; }

    public function getEmailAnnouncements(): bool { return $this->emailAnnouncements; }
    public function setEmailAnnouncements(bool $emailAnnouncements): self { $this->emailAnnouncements = $emailAnnouncements; return $this; }

    public function getPreferredContact(): ?string { return $this->preferredContact; }
    public function setPreferredContact(?string $preferredContact): self { $this->preferredContact = $preferredContact; return $this; }

    public function getDefaultBranch(): ?string { return $this->defaultBranch; }
    public function setDefaultBranch(?string $defaultBranch): self { $this->defaultBranch = $defaultBranch; return $this; }

    public function getTheme(): ?string { return $this->theme; }
    public function setTheme(?string $theme): self { $this->theme = $theme; return $this; }

    public function getPreferredCategories(): ?array { return $this->preferredCategories; }
    public function setPreferredCategories(?array $categories): self { $this->preferredCategories = $categories; return $this; }

    public function isOnboardingCompleted(): bool { return $this->onboardingCompleted; }
    public function setOnboardingCompleted(bool $completed): self { $this->onboardingCompleted = $completed; return $this; }

    public function getFontSize(): ?string { return $this->fontSize; }
    public function setFontSize(?string $fontSize): self { $this->fontSize = $fontSize; return $this; }

    public function getLanguage(): ?string { return $this->language; }
    public function setLanguage(?string $language): self { $this->language = $language; return $this; }

    public function getPin(): ?string { return $this->pin; }
    public function setPin(?string $pin): self { $this->pin = $pin; return $this; }
}
