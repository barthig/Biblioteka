<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetAuthorHandler;
use App\Application\Query\Author\GetAuthorQuery;
use App\Entity\Author;
use App\Repository\AuthorRepository;
use PHPUnit\Framework\TestCase;

class GetAuthorHandlerTest extends TestCase
{
    private AuthorRepository $authorRepository;
    private GetAuthorHandler $handler;

    protected function setUp(): void
    {
        $this->authorRepository = $this->createMock(AuthorRepository::class);
        $this->handler = new GetAuthorHandler($this->authorRepository);
    }

    public function testGetAuthorSuccess(): void
    {
        $author = $this->createMock(Author::class);
        $this->authorRepository->method('find')->with(1)->willReturn($author);

        $query = new GetAuthorQuery(authorId: 1);
        $result = ($this->handler)($query);

        $this->assertSame($author, $result);
    }
}
