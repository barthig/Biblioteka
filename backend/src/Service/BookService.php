<?php
namespace App\Service;

use App\Entity\Book;
use Doctrine\Persistence\ManagerRegistry;

class BookService
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function borrow(Book $book): bool
    {
        if ($book->getCopies() <= 0) {
            return false;
        }

        $book->setCopies($book->getCopies() - 1);

        $em = $this->doctrine->getManager();
        $em->persist($book);
        $em->flush();

        return true;
    }

    public function restore(Book $book): void
    {
        $book->setCopies(min($book->getTotalCopies(), $book->getCopies() + 1));

        $em = $this->doctrine->getManager();
        $em->persist($book);
        $em->flush();
    }
}
