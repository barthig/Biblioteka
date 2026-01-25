<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\CreateLoanCommand;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Loan;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\UserBookInteraction;
use App\Event\BookBorrowedEvent;
use App\Repository\BookCopyRepository;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Service\Book\BookService;
use App\Service\System\SystemSettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
class CreateLoanHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private BookService $bookService,
        private LoanRepository $loanRepository,
        private ReservationRepository $reservationRepository,
        private BookCopyRepository $bookCopyRepository,
        private SystemSettingsService $settingsService,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CreateLoanCommand $command): Loan
    {
        $userRepo = $this->em->getRepository(User::class);
        $bookRepo = $this->em->getRepository(Book::class);

        $user = $userRepo->find($command->userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if ($user->isBlocked()) {
            throw new \RuntimeException('Konto czytelnika jest zablokowane');
        }

        $activeLoans = $this->loanRepository->countActiveByUser($user);
        $loanLimit = $user->getLoanLimit();
        if ($loanLimit > 0 && $activeLoans >= $loanLimit) {
            throw new \RuntimeException('Limit wypożyczeń został osiągnięty');
        }

        $book = $bookRepo->find($command->bookId);
        if (!$book) {
            throw new \RuntimeException('Book not found');
        }

        $preferredCopy = null;
        $reservation = null;

        if ($command->bookCopyId) {
            $preferredCopy = $this->bookCopyRepository->find($command->bookCopyId);
            if (!$preferredCopy) {
                throw new \RuntimeException('Egzemplarz nie znaleziony');
            }

            if ($preferredCopy->getStatus() === BookCopy::STATUS_BORROWED) {
                throw new \RuntimeException('Egzemplarz jest już wypożyczony');
            }
        }

        if ($command->reservationId) {
            $reservation = $this->reservationRepository->find($command->reservationId);
            if (!$reservation || $reservation->getUser()->getId() !== $user->getId()) {
                throw new \RuntimeException('Nieprawidłowa rezerwacja');
            }
        } else {
            $reservation = $this->reservationRepository->findFirstActiveForUserAndBook($user, $book);
        }

        $this->em->beginTransaction();
        try {
            $copy = $this->bookService->borrow($book, $reservation, $preferredCopy, false);
            if (!$copy) {
                $queue = $this->reservationRepository->findActiveByBook($book);
                if (!empty($queue)) {
                    throw new \RuntimeException('Book reserved by another reader');
                }
                throw new \RuntimeException('No copies available');
            }

            $loanDurationDays = $this->settingsService->getLoanDurationDays();

            $loan = (new Loan())
                ->setBook($book)
                ->setBookCopy($copy)
                ->setUser($user)
                ->setDueAt((new \DateTimeImmutable())->modify("+{$loanDurationDays} days"));

            $this->em->persist($loan);

            $interactionRepo = $this->em->getRepository(UserBookInteraction::class);
            $interaction = $interactionRepo->findOneBy(['user' => $user, 'book' => $book]);
            if (!$interaction) {
                $interaction = (new UserBookInteraction())
                    ->setUser($user)
                    ->setBook($book)
                    ->setType(UserBookInteraction::TYPE_READ);
                $this->em->persist($interaction);
            }
            $this->em->flush();
            $this->em->commit();
            
            // Dispatch event
            $this->eventDispatcher->dispatch(new BookBorrowedEvent($loan));
            
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('CreateLoanHandler exception', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            if ($e instanceof \RuntimeException) {
                throw $e;
            }
            throw new \RuntimeException('Nie udało się utworzyć wypożyczenia: ' . $e->getMessage());
        }

        return $loan;
    }
}
