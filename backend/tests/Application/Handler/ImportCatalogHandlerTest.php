<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Catalog\ImportCatalogCommand;
use App\Application\Handler\Command\ImportCatalogHandler;
use App\Entity\Author;
use App\Repository\AuthorRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ImportCatalogHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private AuthorRepository $authorRepository;
    private CategoryRepository $categoryRepository;
    private ImportCatalogHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->authorRepository = $this->createMock(AuthorRepository::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->handler = new ImportCatalogHandler($this->entityManager, $this->authorRepository, $this->categoryRepository);
    }

    public function testImportCatalogSuccess(): void
    {
        $author = $this->createMock(Author::class);
        $this->authorRepository->method('findOneBy')->willReturn($author);
        $this->categoryRepository->method('findBy')->willReturn([]);

        $command = new ImportCatalogCommand(items: [
            [
                'title' => 'Test Book',
                'author' => 'Test Author',
                'isbn' => '1234567890',
            ]
        ]);
        
        $result = ($this->handler)($command);

        $this->assertIsArray($result);
    }
}
