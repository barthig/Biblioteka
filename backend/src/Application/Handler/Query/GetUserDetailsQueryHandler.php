<?php
declare(strict_types=1);

namespace App\Application\Handler\Query;

use App\Application\Query\User\GetUserDetailsQuery;
use App\Exception\NotFoundException;
use App\Repository\UserRepository;
use App\Repository\LoanRepository;
use App\Repository\FineRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetUserDetailsQueryHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private LoanRepository $loanRepository,
        private FineRepository $fineRepository
    ) {
    }

    public function __invoke(GetUserDetailsQuery $query): array
    {
        $user = $this->userRepository->find($query->userId);
        
        if (!$user) {
            throw NotFoundException::forUser($query->userId);
        }

        // Get active loans
        $activeLoans = $this->loanRepository->findBy(
            ['user' => $user, 'returnedAt' => null],
            ['borrowedAt' => 'DESC']
        );

        // Get loan history
        $loanHistory = $this->loanRepository->findBy(
            ['user' => $user],
            ['borrowedAt' => 'DESC'],
            20 // Last 20 loans
        );

        // Get fines
        $fines = $this->fineRepository->findByUser($user);

        $activeFines = array_filter($fines, fn($fine) => !$fine->isPaid());
        $paidFines = array_filter($fines, fn($fine) => $fine->isPaid());

        return [
            'user' => $user,
            'activeLoans' => $activeLoans,
            'loanHistory' => $loanHistory,
            'activeFines' => array_values($activeFines),
            'paidFines' => array_values($paidFines),
            'statistics' => [
                'totalLoans' => count($loanHistory),
                'activeLoansCount' => count($activeLoans),
                'totalFines' => count($fines),
                'activeFinesCount' => count($activeFines),
                'totalFineAmount' => array_sum(array_map(fn($f) => $f->getAmount(), $activeFines))
            ]
        ];
    }
}
