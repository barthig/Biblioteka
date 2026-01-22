<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Query\Book\ExportBooksQuery;
use App\Entity\Book;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Export')]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly SecurityService $security
    ) {}

    #[Route('/api/books/export', methods: ['GET'])]
    #[OA\Get(
        path: '/api/books/export',
        summary: 'Export books to CSV',
        description: 'Exports all books in the catalog to a CSV file',
        tags: ['Export'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'CSV file with books data',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Librarian role required'
            )
        ]
    )]
    public function exportBooks(): Response
    {
        $envelope = $this->queryBus->dispatch(new ExportBooksQuery());
        $books = $envelope->last(HandledStamp::class)?->getResult();

        $response = $this->createExportResponse($books);

        $filename = 'books_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /**
     * @param Book[] $books
     */
    private function createExportResponse(array $books): Response
    {
        if (($_ENV['APP_ENV'] ?? null) === 'test') {
            $handle = fopen('php://temp', 'r+');
            $this->writeCsv($handle, $books);
            rewind($handle);
            $content = stream_get_contents($handle) ?: '';
            fclose($handle);

            return new Response($content);
        }

        return new StreamedResponse(function() use ($books) {
            $handle = fopen('php://output', 'w');
            $this->writeCsv($handle, $books);
            fclose($handle);
        });
    }

    /**
     * @param resource $handle
     * @param Book[] $books
     */
    private function writeCsv($handle, array $books): void
    {
        // UTF-8 BOM for Excel compatibility
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV Header
        fputcsv($handle, [
            'ID',
            'TytuĹ‚',
            'Autor',
            'ISBN',
            'Wydawca',
            'Rok wydania',
            'Kategorie',
            'Sygnatura',
            'Liczba egzemplarzy',
            'DostÄ™pne',
            'WypoĹĽyczone',
            'Ocena Ĺ›rednia',
            'Liczba ocen',
            'JÄ™zyk',
            'Grupa wiekowa',
            'Typ zasobu'
        ]);

        /** @var Book $book */
        foreach ($books as $book) {
            $categories = [];
            foreach ($book->getCategories() as $category) {
                $categories[] = $category->getName();
            }

            fputcsv($handle, [
                $book->getId(),
                $book->getTitle(),
                $book->getAuthor()->getName(),
                $book->getIsbn(),
                $book->getPublisher(),
                $book->getPublicationYear(),
                implode(', ', $categories),
                $book->getSignature(),
                $book->getTotalCopies(),
                $book->getCopies(),
                max(0, $book->getTotalCopies() - $book->getCopies()),
                $book->getAverageRating() ? number_format($book->getAverageRating(), 2) : '',
                $book->getRatingCount(),
                'pl',
                $this->getAgeGroupLabel($book->getTargetAgeGroup()),
                $book->getResourceType() ?? 'book'
            ]);
        }
    }

    private function getAgeGroupLabel(?string $ageGroup): string
    {
        return match($ageGroup) {
            Book::AGE_GROUP_TODDLERS => '0-2 lata',
            Book::AGE_GROUP_PRESCHOOL => '3-6 lat',
            Book::AGE_GROUP_EARLY_SCHOOL => '7-9 lat',
            Book::AGE_GROUP_MIDDLE_GRADE => '10-12 lat',
            Book::AGE_GROUP_YA_EARLY => '13-15 lat',
            Book::AGE_GROUP_YA_LATE => '16+ lat',
            default => ''
        };
    }
}

