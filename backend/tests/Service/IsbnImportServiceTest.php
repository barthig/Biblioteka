<?php
namespace App\Tests\Service;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Service\Maintenance\IsbnImportService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class IsbnImportServiceTest extends TestCase
{
    public function testImportReportsMissingIsbn(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $books = $this->createMock(BookRepository::class);
        $authors = $this->createMock(AuthorRepository::class);
        $categories = $this->createMock(CategoryRepository::class);

        $service = new IsbnImportService($entityManager, $books, $authors, $categories);
        $result = $service->import([['title' => 'Missing']], true);

        $this->assertSame(1, $result['processed']);
        $this->assertSame(0, $result['created']);
        $this->assertCount(1, $result['errors']);
    }

    public function testImportCreatesBook(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $books = $this->createMock(BookRepository::class);
        $authors = $this->createMock(AuthorRepository::class);
        $categories = $this->createMock(CategoryRepository::class);

        $books->method('findOneBy')->willReturn(null);

        $author = (new Author())->setName('Author');
        $authors->method('findOneBy')->willReturn($author);

        $category = (new Category())->setName('Category');
        $categories->method('findOneBy')->willReturn($category);

        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Book::class));
        $entityManager->expects($this->once())->method('flush');

        $service = new IsbnImportService($entityManager, $books, $authors, $categories);
        $result = $service->import([['isbn' => '123', 'title' => 'Title']], false);

        $this->assertSame(1, $result['created']);
    }
}
