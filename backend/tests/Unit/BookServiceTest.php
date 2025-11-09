<?php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Entity\Author;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Repository\BookCopyRepository;
use App\Service\BookService;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

class BookServiceTest extends TestCase
{
    public function testBorrowAndRestore()
    {
        $author = (new Author())->setName('Tester');

        $book = (new Book())
            ->setTitle('T')
            ->setAuthor($author);

        $copy = (new BookCopy())
            ->setBook($book)
            ->setInventoryCode('UNIT-001');

        $book->addInventoryCopy($copy);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->exactly(4))->method('persist')->with($this->logicalOr($book, $copy));
        $em->expects($this->exactly(2))->method('flush');

        $repo = $this->createMock(BookCopyRepository::class);
        $repo->expects($this->once())->method('findAvailableCopies')->with($book, 1)->willReturn([$copy]);

        /** @var ManagerRegistry&\PHPUnit\Framework\MockObject\MockObject $mr */
        $mr = $this->createMock(ManagerRegistry::class);
        $mr->method('getManager')->willReturn($em);
        $mr->method('getRepository')->with(BookCopy::class)->willReturn($repo);

        $svc = new BookService($mr);
        $borrowedCopy = $svc->borrow($book);
        $this->assertInstanceOf(BookCopy::class, $borrowedCopy);
        $this->assertSame(BookCopy::STATUS_BORROWED, $borrowedCopy->getStatus());
        $this->assertEquals(0, $book->getCopies());

        $svc->restore($book, $borrowedCopy);
        $this->assertEquals(1, $book->getCopies());
        $this->assertSame(BookCopy::STATUS_AVAILABLE, $borrowedCopy->getStatus());
    }
}
