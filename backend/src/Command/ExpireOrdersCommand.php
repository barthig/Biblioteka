<?php
namespace App\Command;

use App\Entity\OrderRequest;
use App\Repository\OrderRequestRepository;
use App\Service\OrderLifecycleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:orders:expire', description: 'Marks overdue pickup orders as expired and releases reserved copies')]
class ExpireOrdersCommand extends Command
{
    private OrderRequestRepository $orders;
    private OrderLifecycleService $lifecycle;

    public function __construct(OrderRequestRepository $orders, OrderLifecycleService $lifecycle)
    {
        parent::__construct();
        $this->orders = $orders;
        $this->lifecycle = $lifecycle;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $overdue = $this->orders->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->andWhere('o.pickupDeadline IS NOT NULL')
            ->andWhere('o.pickupDeadline < :now')
            ->leftJoin('o.bookCopy', 'copy')->addSelect('copy')
            ->leftJoin('o.book', 'book')->addSelect('book')
            ->setParameter('status', OrderRequest::STATUS_READY)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();

        $expired = $this->lifecycle->expireOrders($overdue);
        $output->writeln(sprintf('Expired %d overdue orders.', $expired));

        return Command::SUCCESS;
    }
}
