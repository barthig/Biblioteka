<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Fine\CancelFineCommand;
use App\Application\Handler\Command\CancelFineHandler;
use App\Entity\Fine;
use App\Repository\FineRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CancelFineHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private FineRepository $fineRepository;
    private CancelFineHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->fineRepository = $this->createMock(FineRepository::class);
        $this->handler = new CancelFineHandler($this->em, $this->fineRepository);
    }

    public function testCancelFineSuccess(): void
    {
        $fine = $this->createMock(Fine::class);
        $fine->method('getPaidAt')->willReturn(null);

        $this->fineRepository->method('find')->with(1)->willReturn($fine);

        $this->em->expects($this->once())->method('remove')->with($fine);
        $this->em->expects($this->once())->method('flush');

        $command = new CancelFineCommand(fineId: 1);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenFineNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Fine not found');

        $this->fineRepository->method('find')->with(999)->willReturn(null);

        $command = new CancelFineCommand(fineId: 999);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenFineAlreadyPaid(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Cannot cancel a paid fine');

        $fine = $this->createMock(Fine::class);
        $fine->method('getPaidAt')->willReturn(new \DateTimeImmutable());

        $this->fineRepository->method('find')->with(1)->willReturn($fine);

        $command = new CancelFineCommand(fineId: 1);
        ($this->handler)($command);
    }
}
