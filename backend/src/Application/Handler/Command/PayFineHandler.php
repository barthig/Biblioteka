<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Fine\PayFineCommand;
use App\Entity\Fine;
use App\Repository\FineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PayFineHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FineRepository $fineRepository
    ) {
    }

    public function __invoke(PayFineCommand $command): Fine
    {
        $fine = $this->fineRepository->find($command->fineId);
        
        if (!$fine) {
            throw new NotFoundHttpException('Fine not found');
        }

        $isOwner = $fine->getLoan()->getUser()->getId() === $command->userId;
        
        if (!($command->isLibrarian || $isOwner)) {
            throw new AccessDeniedHttpException('Forbidden');
        }

        if ($fine->getPaidAt() !== null) {
            throw new BadRequestHttpException('Fine already paid');
        }

        $fine->markAsPaid();
        
        $this->entityManager->persist($fine);
        $this->entityManager->flush();

        return $fine;
    }
}
