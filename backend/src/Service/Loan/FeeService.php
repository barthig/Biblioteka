<?php
namespace App\Service\Loan;

use App\Entity\Fine;
use App\Repository\FineRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class FeeService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly FineRepository $fines,
        private readonly EntityManagerInterface $em
    ) {
    }

    /**
     * @return Fine[]
     */
    public function listOutstandingFees(int $userId): array
    {
        $user = $this->users->find($userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        return $this->fines->findOutstandingByUser($user);
    }

    public function markFeePaid(int $userId, int $feeId): Fine
    {
        $user = $this->users->find($userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $fee = $this->fines->findOneByIdAndUser($feeId, $user);
        if (!$fee) {
            throw new \RuntimeException('Fee not found');
        }

        if ($fee->isPaid()) {
            throw new \InvalidArgumentException('Fee already paid');
        }

        $fee->markAsPaid();
        $this->em->flush();

        return $fee;
    }
}

