<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Book\CreateBookCommand;
use App\Application\Handler\Command\CreateBookHandler;
use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Repository\AuthorRepository;
use App\Repository\CategoryRepository;
use App\Service\User\NotificationService;
use App\Service\Notification\NotificationSender;
use App\Repository\UserRepository;
use App\Repository\NotificationLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreateBookHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private AuthorRepository $authorRepository;
    private CategoryRepository $categoryRepository;
    private NotificationService $notificationService;
    private CreateBookHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->authorRepository = $this->createMock(AuthorRepository::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $sender = $this->createMock(NotificationSender::class);
        $logger = $this->createMock(LoggerInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $notificationLogs = $this->createMock(NotificationLogRepository::class);
        $this->notificationService = new NotificationService(
            $sender,
            $logger,
            $userRepository,
            $notificationLogs,
            $this->em
        );

        $this->handler = new CreateBookHandler(
            $this->em,
            $this->authorRepository,
            $this->categoryRepository,
            $this->notificationService
        );
    }

    public function testCreateBookSuccess(): void
    {
        $author = $this->createMock(Author::class);
        $category = $this->createMock(Category::class);

        $this->authorRepository->method('find')->with(1)->willReturn($author);
        $this->categoryRepository->method('findBy')->with(['id' => [1]])->willReturn([$category]);

        $this->em->expects($this->atLeastOnce())->method('persist');
        $this->em->expects($this->atLeast(2))->method('flush');

        $command = new CreateBookCommand(
            title: 'Test Book',
            authorId: 1,
            isbn: '978-3-16-148410-0',
            description: 'Test description',
            categoryIds: [1],
            publicationYear: 2023,
            totalCopies: 5,
            availableCopies: 5
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(Book::class, $result);
    }

    public function testThrowsExceptionWhenAuthorNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Author not found');

        $this->authorRepository->method('find')->with(999)->willReturn(null);

        $command = new CreateBookCommand(
            title: 'Test Book',
            authorId: 999,
            isbn: '978-3-16-148410-0',
            description: 'Test description',
            categoryIds: [1],
            publicationYear: 2023,
            totalCopies: 5,
            availableCopies: 5
        );

        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenCategoryNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('One or more categories not found');

        $author = $this->createMock(Author::class);
        $category = $this->createMock(Category::class);

        $this->authorRepository->method('find')->with(1)->willReturn($author);
        $this->categoryRepository->method('findBy')->with(['id' => [1, 999]])->willReturn([$category]);

        $command = new CreateBookCommand(
            title: 'Test Book',
            authorId: 1,
            isbn: '978-3-16-148410-0',
            description: 'Test description',
            categoryIds: [1, 999],
            publicationYear: 2023,
            totalCopies: 5,
            availableCopies: 5
        );

        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenNoCategoriesProvided(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('At least one category is required');

        $author = $this->createMock(Author::class);
        $this->authorRepository->method('find')->with(1)->willReturn($author);
        $this->categoryRepository->method('findBy')->with(['id' => []])->willReturn([]);

        $command = new CreateBookCommand(
            title: 'Test Book',
            authorId: 1,
            isbn: '978-3-16-148410-0',
            description: 'Test description',
            categoryIds: [],
            publicationYear: 2023,
            totalCopies: 5,
            availableCopies: 5
        );

        ($this->handler)($command);
    }
}
