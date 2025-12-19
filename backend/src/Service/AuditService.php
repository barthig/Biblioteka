<?php
namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack
    ) {
    }

    /**
     * Loguje operację w systemie audit log
     */
    /**
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    public function log(
        string $entityType,
        ?int $entityId,
        string $action,
        ?User $user = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): void {
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request?->getClientIp();

        $log = new AuditLog();
        $log->setEntityType($entityType)
            ->setEntityId($entityId)
            ->setAction($action)
            ->setUser($user)
            ->setIpAddress($ipAddress)
            ->setDescription($description);

        if ($oldValues !== null) {
            $log->setOldValues(json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        if ($newValues !== null) {
            $log->setNewValues(json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $this->em->persist($log);
        $this->em->flush();
    }

    /**
     * Loguje utworzenie encji
     */
    /**
     * @param array<string, mixed> $values
     */
    public function logCreate(string $entityType, int $entityId, ?User $user, array $values): void
    {
        $this->log($entityType, $entityId, 'CREATE', $user, null, $values);
    }

    /**
     * Loguje aktualizację encji
     */
    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     */
    public function logUpdate(string $entityType, int $entityId, ?User $user, array $oldValues, array $newValues): void
    {
        $this->log($entityType, $entityId, 'UPDATE', $user, $oldValues, $newValues);
    }

    /**
     * Loguje usunięcie encji
     */
    /**
     * @param array<string, mixed> $oldValues
     */
    public function logDelete(string $entityType, int $entityId, ?User $user, array $oldValues): void
    {
        $this->log($entityType, $entityId, 'DELETE', $user, $oldValues, null);
    }

    /**
     * Loguje akcję użytkownika (np. LOGIN, LOGOUT)
     */
    public function logUserAction(string $action, ?User $user, ?string $description = null): void
    {
        $this->log('User', $user?->getId(), $action, $user, null, null, $description);
    }

    /**
     * Loguje wypożyczenie książki
     */
    public function logLoan(int $loanId, User $user, int $bookId, string $action = 'LOAN_CREATE'): void
    {
        $this->log('Loan', $loanId, $action, $user, null, ['bookId' => $bookId, 'userId' => $user->getId()]);
    }

    /**
     * Loguje zwrot książki
     */
    public function logReturn(int $loanId, User $user, int $bookId): void
    {
        $this->log('Loan', $loanId, 'LOAN_RETURN', $user, null, ['bookId' => $bookId]);
    }
}
