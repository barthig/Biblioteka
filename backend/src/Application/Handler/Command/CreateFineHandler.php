<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Fine\CreateFineCommand;
use App\Entity\Fine;
use App\Entity\Loan;
use App\Event\FineCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler(bus: 'command.bus')]
class CreateFineHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(CreateFineCommand $command): Fine
    {
        $loan = $this->entityManager->getRepository(Loan::class)->find($command->loanId);
        
        if (!$loan) {
            throw new NotFoundHttpException('Loan not found');
        }

        $fine = (new Fine())
            ->setLoan($loan)
            ->setAmount(number_format((float) $command->amount, 2, '.', ''))
            ->setCurrency($command->currency)
            ->setReason($command->reason);

        $this->entityManager->persist($fine);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new FineCreatedEvent($fine));

        return $fine;
    }
}
