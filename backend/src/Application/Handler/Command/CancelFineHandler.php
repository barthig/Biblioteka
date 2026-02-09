<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Fine\CancelFineCommand;
use App\Repository\FineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class CancelFineHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FineRepository $fineRepository
    ) {
    }

    public function __invoke(CancelFineCommand $command): void
    {
        $fine = $this->fineRepository->find($command->fineId);
        
        if (!$fine) {
            throw new NotFoundHttpException('Fine not found');
        }

        if ($fine->getPaidAt() !== null) {
            throw new BadRequestHttpException('Cannot cancel a paid fine');
        }

        $this->entityManager->remove($fine);
        $this->entityManager->flush();
    }
}
