<?php
declare(strict_types=1);
namespace App\Service\Maintenance;

use App\Entity\User;
use App\Repository\FineRepository;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class PatronAnonymizer
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $users,
        private LoanRepository $loans,
        private ReservationRepository $reservations,
        private FineRepository $fines
    ) {
    }

    /**
     * @return array{
     *     candidates: int,
     *     anonymized: int,
    *     skippedActive: int,
    *     skippedBlocked: int,
    *     skippedAnonymized: int,
     *     dryRun: bool,
     *     userIds: array<int, int>
     * }
     */
    public function anonymize(
        \DateTimeImmutable $inactiveBefore,
        int $limit,
        bool $dryRun = false
    ): array {
        $limit = max(1, $limit);

        $candidates = $this->users->createQueryBuilder('u')
            ->andWhere('u.updatedAt <= :cutoff')
            ->orderBy('u.updatedAt', 'ASC')
            ->setParameter('cutoff', $inactiveBefore)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $stats = [
            'candidates' => count($candidates),
            'anonymized' => 0,
            'skippedActive' => 0,
            'skippedBlocked' => 0,
            'skippedAnonymized' => 0,
            'dryRun' => $dryRun,
            'userIds' => [],
        ];

        foreach ($candidates as $user) {
            if (!$user instanceof User) {
                continue;
            }

            if ($user->isBlocked()) {
                ++$stats['skippedBlocked'];
                continue;
            }

            if ($this->isAlreadyAnonymized($user)) {
                ++$stats['skippedAnonymized'];
                continue;
            }

            if ($this->loans->countActiveByUser($user) > 0) {
                ++$stats['skippedActive'];
                continue;
            }

            if ($this->reservations->countActiveByUser($user) > 0) {
                ++$stats['skippedActive'];
                continue;
            }

            if ($this->fines->sumOutstandingByUser($user) > 0.0) {
                ++$stats['skippedActive'];
                continue;
            }

            if ($dryRun) {
                ++$stats['anonymized'];
                $stats['userIds'][] = (int) $user->getId();
                continue;
            }

            $this->scrubUser($user);
            $this->entityManager->persist($user);
            ++$stats['anonymized'];
            $stats['userIds'][] = (int) $user->getId();
        }

        if (!$dryRun && $stats['anonymized'] > 0) {
            $this->entityManager->flush();
        }

        return $stats;
    }

    private function scrubUser(User $user): void
    {
        $suffix = bin2hex(random_bytes(4));
        $identifier = $user->getId() ?? random_int(1000, 9999);
        $user->setEmail(sprintf('anon+%d-%s@example.invalid', $identifier, $suffix));
        $user->setName('Anonimowy Czytelnik');
        $user->setPhoneNumber(null);
        $user->setAddressLine(null);
        $user->setCity(null);
        $user->setPostalCode(null);
        $user->unblock();
        $user->setPendingApproval(false);
        $user->setRoles(['ROLE_USER']);
        $user->setLoanLimit(User::GROUP_LIMITS[$user->getMembershipGroup()] ?? User::GROUP_LIMITS[User::GROUP_STANDARD]);
        $user->requireVerification();
        $user->setPassword(password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT));
    }

    private function isAlreadyAnonymized(User $user): bool
    {
        return str_contains($user->getEmail(), '@example.invalid');
    }
}
