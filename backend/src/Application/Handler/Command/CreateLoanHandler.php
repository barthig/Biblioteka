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
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
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
        private EntityManagerInterface $entityManager,
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
        $userRepo = $this->entityManager->getRepository(User::class);
        $bookRepo = $this->entityManager->getRepository(Book::class);

        $user = $userRepo->find($command->userId);
        if (!$user) {
            throw NotFoundException::forUser($command->userId);
        }

        if ($user->isBlocked()) {
            throw BusinessLogicException::invalidState('User account is blocked.');
        }

        $activeLoans = $this->loanRepository->countActiveByUser($user);
        $loanLimit = $user->getLoanLimit();
        if ($loanLimit > 0 && $activeLoans >= $loanLimit) {
            throw BusinessLogicException::maxLoansReached($loanLimit);
        }

        $book = $bookRepo->find($command->bookId);
        if (!$book) {
            throw NotFoundException::forBook($command->bookId);
        }

        $preferredCopy = null;
        $reservation = null;

        if ($command->bookCopyId) {
            $preferredCopy = $this->bookCopyRepository->find($command->bookCopyId);
            if (!$preferredCopy) {
                throw NotFoundException::forEntity('BookCopy', $command->bookCopyId);
            }

            if ($preferredCopy->getStatus() === BookCopy::STATUS_BORROWED) {
                throw BusinessLogicException::bookNotAvailable($command->bookCopyId);
            }
        }

        if ($command->reservationId) {
            $reservation = $this->reservationRepository->find($command->reservationId);
            if (!$reservation || $reservation->getUser()->getId() !== $user->getId()) {
                throw NotFoundException::forReservation($command->reservationId);
            }
        } else {
            $reservation = $this->reservationRepository->findFirstActiveForUserAndBook($user, $book);
        }

        $this->entityManager->beginTransaction();
        try {
            $copy = $this->bookService->borrow($book, $reservation, $preferredCopy, false);
            if (!$copy) {
                $queue = $this->reservationRepository->findActiveByBook($book);
                if (!empty($queue)) {
                    throw BusinessLogicException::bookNotAvailable($book->getId());
                }
                throw BusinessLogicException::noCopiesAvailable();
            }

            $loanDurationDays = $this->settingsService->getLoanDurationDays();

            $loan = (new Loan())
                ->setBook($book)
                ->setBookCopy($copy)
                ->setUser($user)
                ->setDueAt((new \DateTimeImmutable())->modify("+{$loanDurationDays} days"));

            $this->entityManager->persist($loan);

            $interactionRepo = $this->entityManager->getRepository(UserBookInteraction::class);
            $interaction = $interactionRepo->findOneBy(['user' => $user, 'book' => $book]);
            if (!$interaction) {
                $interaction = (new UserBookInteraction())
                    ->setUser($user)
                    ->setBook($book)
                    ->setType(UserBookInteraction::TYPE_READ);
                $this->entityManager->persist($interaction);
            }
            $this->entityManager->flush();
            $this->entityManager->commit();
            
            // Dispatch event
            $this->eventDispatcher->dispatch(new BookBorrowedEvent($loan));
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('CreateLoanHandler exception', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            if ($e instanceof \App\Exception\AppException) {
                throw $e;
            }
            throw BusinessLogicException::operationFailed('CreateLoan', $e->getMessage());
        }

        return $loan;
    }
}
