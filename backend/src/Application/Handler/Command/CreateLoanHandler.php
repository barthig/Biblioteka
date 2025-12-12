<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\CreateLoanCommand;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Loan;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\BookCopyRepository;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Service\BookService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateLoanHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private BookService $bookService,
        private LoanRepository $loanRepository,
        private ReservationRepository $reservationRepository,
        private BookCopyRepository $bookCopyRepository
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

            $loan = (new Loan())
                ->setBook($book)
                ->setBookCopy($copy)
                ->setUser($user)
                ->setDueAt((new \DateTimeImmutable())->modify('+14 days'));

            $this->em->persist($loan);
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            if ($e instanceof \RuntimeException) {
                throw $e;
            }
            throw new \RuntimeException('Nie udało się utworzyć wypożyczenia');
        }

        return $loan;
    }
}
