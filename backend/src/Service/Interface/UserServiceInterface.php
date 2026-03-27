<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Entity\User;

interface UserServiceInterface
{
    public function getUserById(int $userId): ?User;

    public function getUserByEmail(string $email): ?User;

    public function createUser(array $data): User;

    public function updateUser(User $user, array $data): User;

    public function blockUser(User $user, ?string $reason = null): void;

    public function unblockUser(User $user): void;

    public function isBlocked(User $user): bool;

    public function getUserStatistics(User $user): array;

    public function changePassword(User $user, string $currentPassword, string $newPassword): void;

    public function searchUsers(array $filters, int $page = 1, int $limit = 20): array;
}
