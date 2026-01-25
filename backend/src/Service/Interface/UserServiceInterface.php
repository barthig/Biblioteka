<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Entity\User;

/**
 * Interface for User-related business operations
 */
interface UserServiceInterface
{
    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?User;

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): ?User;

    /**
     * Create a new user
     * 
     * @throws \App\Exception\EmailAlreadyExistsException
     */
    public function createUser(array $data): User;

    /**
     * Update user profile
     */
    public function updateUser(User $user, array $data): User;

    /**
     * Block user account
     * 
     * @param string|null $reason Reason for blocking
     */
    public function blockUser(User $user, ?string $reason = null): void;

    /**
     * Unblock user account
     */
    public function unblockUser(User $user): void;

    /**
     * Check if user is blocked
     */
    public function isBlocked(User $user): bool;

    /**
     * Get user statistics (loans count, fines, etc.)
     */
    public function getUserStatistics(User $user): array;

    /**
     * Change user password
     * 
     * @throws \App\Exception\InvalidPasswordException
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void;

    /**
     * Search users with filters
     */
    public function searchUsers(array $filters, int $page = 1, int $limit = 20): array;
}
