<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Loan\DeleteLoanCommand;
use App\Application\Handler\Command\DeleteLoanHandler;
use App\Entity\Loan;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteLoanHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private LoanRepository $loanRepository;
    private DeleteLoanHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->loanRepository = $this->createMock(LoanRepository::class);

        $this->handler = new DeleteLoanHandler(
            $this->em,
            $this->loanRepository
        );
    }

    public function testDeleteLoanSuccess(): void
    {
        $loan = $this->createMock(Loan::class);

        $this->loanRepository->method('find')->with(1)->willReturn($loan);

        $this->em->expects($this->once())->method('remove')->with($loan);
        $this->em->expects($this->once())->method('flush');

        $command = new DeleteLoanCommand(loanId: 1);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenLoanNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Loan not found');

        $this->loanRepository->method('find')->with(999)->willReturn(null);

        $command = new DeleteLoanCommand(loanId: 999);
        ($this->handler)($command);
    }
}
