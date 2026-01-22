<?php
namespace App\Tests\Service;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Service\Notification\NewsletterComposer;
use PHPUnit\Framework\TestCase;

class NewsletterComposerTest extends TestCase
{
    public function testComposeBuildsContent(): void
    {
        $author = (new Author())->setName('Author');
        $category = (new Category())->setName('Fiction');
        $book = (new Book())->setTitle('Title')->setAuthor($author)->addCategory($category);
        $book->setPublicationYear(2024);

        $composer = new NewsletterComposer();
        $content = $composer->compose([$book], 7, ['email']);

        $this->assertStringContainsString('Nowo', $content->getSubject());
        $this->assertStringContainsString('Title', $content->getTextBody());
        $this->assertSame(['email'], $content->getChannels());
    }
}
