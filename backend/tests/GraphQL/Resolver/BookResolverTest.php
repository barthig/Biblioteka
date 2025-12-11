<?php

namespace App\Tests\GraphQL\Resolver;

use App\GraphQL\Resolver\BookResolver;
use App\Repository\BookRepository;
use PHPUnit\Framework\TestCase;

class BookResolverTest extends TestCase
{
    private BookRepository $bookRepository;
    private BookResolver $resolver;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->resolver = new BookResolver($this->bookRepository);
    }

    public function testGetBookNotFound(): void
    {
        $this->bookRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->resolver->getBook(999);

        $this->assertNull($result);
    }

    public function testResolverExists(): void
    {
        $this->assertInstanceOf(BookResolver::class, $this->resolver);
    }
}


