<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Fine\PayFineCommand;
use App\Application\Handler\Command\PayFineHandler;
use App\Entity\Fine;
use App\Entity\Loan;
use App\Entity\User;
use App\Repository\FineRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PayFineHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private FineRepository $fineRepository;
    private PayFineHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->fineRepository = $this->createMock(FineRepository::class);
        $this->handler = new PayFineHandler($this->em, $this->fineRepository);
    }

    public function testPayFineSuccessAsLibrarian(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $loan = $this->createMock(Loan::class);
        $loan->method('getUser')->willReturn($user);

        $fine = $this->createMock(Fine::class);
        $fine->method('getLoan')->willReturn($loan);
        $fine->method('getPaidAt')->willReturn(null);
        $fine->expects($this->once())->method('markAsPaid');

        $this->fineRepository->method('find')->with(1)->willReturn($fine);

        $this->em->expects($this->once())->method('persist')->with($fine);
        $this->em->expects($this->once())->method('flush');

        $command = new PayFineCommand(fineId: 1, userId: 2, isLibrarian: true);
        $result = ($this->handler)($command);

        $this->assertSame($fine, $result);
    }

    public function testPayFineSuccessAsOwner(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $loan = $this->createMock(Loan::class);
        $loan->method('getUser')->willReturn($user);

        $fine = $this->createMock(Fine::class);
        $fine->method('getLoan')->willReturn($loan);
        $fine->method('getPaidAt')->willReturn(null);
        $fine->expects($this->once())->method('markAsPaid');

        $this->fineRepository->method('find')->with(1)->willReturn($fine);

        $this->em->expects($this->once())->method('persist')->with($fine);
        $this->em->expects($this->once())->method('flush');

        $command = new PayFineCommand(fineId: 1, userId: 1, isLibrarian: false);
        $result = ($this->handler)($command);

        $this->assertSame($fine, $result);
    }

    public function testThrowsExceptionWhenFineNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Fine not found');

        $this->fineRepository->method('find')->with(999)->willReturn(null);

        $command = new PayFineCommand(fineId: 999, userId: 1, isLibrarian: false);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenNotAuthorized(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Forbidden');

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $loan = $this->createMock(Loan::class);
        $loan->method('getUser')->willReturn($user);

        $fine = $this->createMock(Fine::class);
        $fine->method('getLoan')->willReturn($loan);
        $fine->method('getPaidAt')->willReturn(null);

        $this->fineRepository->method('find')->with(1)->willReturn($fine);

        $command = new PayFineCommand(fineId: 1, userId: 2, isLibrarian: false);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenFineAlreadyPaid(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Fine already paid');

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $loan = $this->createMock(Loan::class);
        $loan->method('getUser')->willReturn($user);

        $fine = $this->createMock(Fine::class);
        $fine->method('getLoan')->willReturn($loan);
        $fine->method('getPaidAt')->willReturn(new \DateTimeImmutable());

        $this->fineRepository->method('find')->with(1)->willReturn($fine);

        $command = new PayFineCommand(fineId: 1, userId: 1, isLibrarian: false);
        ($this->handler)($command);
    }
}
