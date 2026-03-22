<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Fine\CreateFineCommand;
use App\Application\Handler\Command\CreateFineHandler;
use App\Entity\Fine;
use App\Entity\Loan;
use App\Event\FineCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CreateFineHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private EventDispatcherInterface $eventDispatcher;
    private CreateFineHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->handler = new CreateFineHandler($this->em, $this->eventDispatcher);
    }

    public function testCreateFineSuccess(): void
    {
        $loan = $this->createMock(Loan::class);

        $loanRepository = $this->createMock(EntityRepository::class);
        $loanRepository->method('find')->with(1)->willReturn($loan);

        $this->em->method('getRepository')->with(Loan::class)->willReturn($loanRepository);
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Fine::class));
        $this->em->expects($this->once())->method('flush');
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(FineCreatedEvent::class));

        $command = new CreateFineCommand(
            loanId: 1,
            amount: '25.50',
            currency: 'PLN',
            reason: 'Overdue'
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(Fine::class, $result);
    }

    public function testThrowsExceptionWhenLoanNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Loan not found');

        $loanRepository = $this->createMock(EntityRepository::class);
        $loanRepository->method('find')->with(999)->willReturn(null);

        $this->em->method('getRepository')->with(Loan::class)->willReturn($loanRepository);

        $command = new CreateFineCommand(
            loanId: 999,
            amount: '25.50',
            currency: 'PLN',
            reason: 'Overdue'
        );

        ($this->handler)($command);
    }
}
