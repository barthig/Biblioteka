<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\\Repository\\SystemSettingRepository')]
#[ORM\Table(name: 'system_setting')]
class SystemSetting
{
    public const TYPE_STRING = 'string';
    public const TYPE_INT = 'int';
    public const TYPE_FLOAT = 'float';
    public const TYPE_BOOL = 'bool';
    public const TYPE_JSON = 'json';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 120, unique: true)]
    private string $settingKey;

    #[ORM\Column(type: 'text')]
    private string $settingValue;

    #[ORM\Column(type: 'string', length: 16)]
    private string $valueType = self::TYPE_STRING;

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

    public function getKey(): string
    {
        return $this->settingKey;
    }

    public function setKey(string $key): self
    {
        $this->settingKey = strtolower(trim($key));
        $this->touch();
        return $this;
    }

    public function getValueType(): string
    {
        return $this->valueType;
    }

    public function setValueType(string $type): self
    {
        $normalized = strtolower(trim($type));
        if (!in_array($normalized, [self::TYPE_STRING, self::TYPE_INT, self::TYPE_FLOAT, self::TYPE_BOOL, self::TYPE_JSON], true)) {
            $normalized = self::TYPE_STRING;
        }
        $this->valueType = $normalized;
        $this->touch();
        return $this;
    }

    public function setValueFromMixed(mixed $value): self
    {
        $this->settingValue = $this->normalizeValue($value);
        $this->touch();
        return $this;
    }

    public function getRawValue(): string
    {
        return $this->settingValue;
    }

    public function getValue(): mixed
    {
        return $this->denormalizeValue($this->settingValue);
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

    private function normalizeValue(mixed $value): string
    {
        return match ($this->valueType) {
            self::TYPE_BOOL => $value ? '1' : '0',
            self::TYPE_INT => (string) (int) $value,
            self::TYPE_FLOAT => number_format((float) $value, 2, '.', ''),
            self::TYPE_JSON => json_encode($value, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }

    private function denormalizeValue(string $stored): mixed
    {
        return match ($this->valueType) {
            self::TYPE_BOOL => $stored === '1',
            self::TYPE_INT => (int) $stored,
            self::TYPE_FLOAT => (float) $stored,
            self::TYPE_JSON => $stored !== '' ? json_decode($stored, true, 512, JSON_THROW_ON_ERROR) : null,
            default => $stored,
        };
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
