<?php
declare(strict_types=1);
namespace App\Service\System;

use App\Entity\SystemSetting;
use App\Repository\SystemSettingRepository;
use Doctrine\ORM\EntityManagerInterface;

class SystemSettingsService
{
    /** @var array<string, mixed> */
    private array $cache = [];
    
    /** @var array<string, mixed> */
    private array $defaults = [
        'loanLimitPerUser' => 5,
        'loanDurationDays' => 14,
        'notificationsEnabled' => true,
    ];

    public function __construct(
        private SystemSettingRepository $settingRepository,
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Get setting value by key with fallback to default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $setting = $this->settingRepository->findOneBy(['settingKey' => $key]);
        
        if ($setting) {
            $value = $setting->getValue();
            $this->cache[$key] = $value;
            return $value;
        }

        // Check if there's a system default
        if (array_key_exists($key, $this->defaults)) {
            return $this->defaults[$key];
        }

        return $default;
    }

    /**
     * Set setting value
     */
    public function set(string $key, mixed $value, ?string $description = null): void
    {
        $setting = $this->settingRepository->findOneBy(['settingKey' => $key]);
        
        if (!$setting) {
            $setting = new SystemSetting();
            $setting->setKey($key);
            if ($description) {
                $setting->setDescription($description);
            }
        }

        // Determine type
        $type = match(true) {
            is_bool($value) => SystemSetting::TYPE_BOOL,
            is_int($value) => SystemSetting::TYPE_INT,
            is_float($value) => SystemSetting::TYPE_FLOAT,
            is_array($value) => SystemSetting::TYPE_JSON,
            default => SystemSetting::TYPE_STRING
        };

        $setting->setValueType($type);
        $setting->setValueFromMixed($value);

        $this->em->persist($setting);
        $this->em->flush();

        // Update cache
        $this->cache[$key] = $value;
    }

    /**
     * Get all settings as array
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        $settings = [];
        
        // Start with defaults
        foreach ($this->defaults as $key => $value) {
            $settings[$key] = $this->get($key);
        }

        // Add any additional settings from DB
        $allSettings = $this->settingRepository->findAll();
        foreach ($allSettings as $setting) {
            $key = $setting->getKey();
            if (!isset($settings[$key])) {
                $settings[$key] = $setting->getValue();
            }
        }

        return $settings;
    }

    /**
     * Update multiple settings at once
     * @param array<string, mixed> $settings
     */
    public function updateMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Get loan limit per user
     */
    public function getLoanLimitPerUser(): int
    {
        return (int) $this->get('loanLimitPerUser', 5);
    }

    /**
     * Get loan duration in days
     */
    public function getLoanDurationDays(): int
    {
        return (int) $this->get('loanDurationDays', 14);
    }

    /**
     * Check if notifications are enabled
     */
    public function areNotificationsEnabled(): bool
    {
        return (bool) $this->get('notificationsEnabled', true);
    }
}


