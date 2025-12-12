<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Loan\ExtendLoanCommand;
use App\Application\Handler\Command\ExtendLoanHandler;
use App\Entity\Book;
use App\Entity\Loan;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ExtendLoanHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private LoanRepository $loanRepository;
    private ReservationRepository $reservationRepository;
    private ExtendLoanHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->loanRepository = $this->createMock(LoanRepository::class);