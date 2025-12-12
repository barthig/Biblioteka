<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Book\UpdateBookCommand;
use App\Application\Handler\Command\UpdateBookHandler;
use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateBookHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private BookRepository $bookRepository;
    private AuthorRepository $authorRepository;
    private CategoryRepository $categoryRepository;
    private UpdateBookHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->authorRepository = $this->createMock(AuthorRepository::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);

        $this->handler = new UpdateBookHandler(
            $this->em,
            $this->bookRepository,
            $this->authorRepository,
            $this->categoryRepository
        );
    }

    public function testUpdateBookSuccess(): void
    {
        $book = $this->createMock(Book::class);
        $author = $this->createMock(Author::class);
        $category = $this->createMock(Category::class);

        $book->expects($this->once())->method('setTitle')->with('Updated Title');
        $book->expects($this->once())->method('setAuthor')->with($author);

        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->authorRepository->method('find')->with(1)->willReturn($author);
        $this->categoryRepository->method('findBy')->with(['id' => [1]])->willReturn([$category]);

        $this->em->expects($this->once())->method('persist')->with($book);
        $this->em->expects($this->once())->method('flush');

        $command = new UpdateBookCommand(
            bookId: 1,
            title: 'Updated Title',
            authorId: 1,
            categoryIds: [1]
        );

        $result = ($this->handler)($command);

        $this->assertSame($book, $result);
    }

    public function testThrowsExceptionWhenBookNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Book not found');

        $this->bookRepository->method('find')->with(999)->willReturn(null);

        $command = new UpdateBookCommand(bookId: 999);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenAuthorNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Author not found');

        $book = $this->createMock(Book::class);
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->authorRepository->method('find')->with(999)->willReturn(null);

        $command = new UpdateBookCommand(bookId: 1, authorId: 999);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenCategoriesEmpty(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('At least one category is required');

        $book = $this->createMock(Book::class);
        $this->bookRepository->method('find')->with(1)->willReturn($book);

        $command = new UpdateBookCommand(bookId: 1, categoryIds: []);
        ($this->handler)($command);
    }
}
