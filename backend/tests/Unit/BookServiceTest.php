<?php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Service\BookService;
use App\Entity\Book;

class BookServiceTest extends TestCase
{
    public function testBorrowAndRestore()
    {
        // create a book with 2 copies
        $book = new Book();
        $book->setTitle('T')->setAuthor('A')->setCopies(2);

        // mock an entity manager
        $em = $this->createMock(\stdClass::class);
        $em->method('persist');
        $em->method('flush');

        // mock manager registry
        $mr = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $mr->method('getManager')->willReturn($em);

        $svc = new BookService($mr);
        $this->assertTrue($svc->borrow($book));
        $this->assertEquals(1, $book->getCopies());

        $svc->restore($book);
        $this->assertEquals(2, $book->getCopies());
    }
}
