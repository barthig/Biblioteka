<?php
namespace App\Service\Notification;

use App\Entity\Book;

class NewsletterComposer
{
    /**
     * @param Book[] $books
     * @param string[] $channels
     */
    public function compose(array $books, int $daysWindow, array $channels): NotificationContent
    {
        $count = count($books);
        $subject = sprintf('Nowości w bibliotece (%d tytułów)', $count);

        $introText = sprintf(
            'W ciągu ostatnich %d dni dodaliśmy %d nowych tytułów. Zobacz najciekawsze z nich:',
            $daysWindow,
            $count
        );

        $textLines = [
            'Cześć!',
            '',
            $introText,
            '',
        ];

        $htmlItems = [];
        foreach ($books as $book) {
            $description = $this->formatBookLine($book);
            $textLines[] = '- ' . $description;
            $htmlItems[] = sprintf('<li>%s</li>', htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        $textLines[] = '';
        $textLines[] = 'Wejdź do katalogu online, aby sprawdzić dostępność i zarezerwować książkę.';
        $textLines[] = 'Pozdrawiamy,';
        $textLines[] = 'Zespół Biblioteki';

        $textBody = implode("\n", $textLines);
        $htmlBody = sprintf(
            '<p>%s</p><ul>%s</ul><p>Wejdź do katalogu i zarezerwuj interesujące Cię pozycje.</p><p>Pozdrawiamy,<br/>Zespół Biblioteki</p>',
            htmlspecialchars($introText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            implode('', $htmlItems)
        );

        return new NotificationContent($subject, $textBody, $htmlBody, $channels);
    }

    private function formatBookLine(Book $book): string
    {
        $author = $book->getAuthor()->getName();
        $categories = [];
        foreach ($book->getCategories() as $category) {
            $categories[] = $category->getName();
        }

        $categorySuffix = $categories !== [] ? ' [' . implode(', ', $categories) . ']' : '';
        $yearSuffix = $book->getPublicationYear() ? sprintf(' (%d)', $book->getPublicationYear()) : '';

        return sprintf('%s — %s%s%s', $book->getTitle(), $author, $yearSuffix, $categorySuffix);
    }
}
