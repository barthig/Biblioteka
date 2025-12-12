<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Review\ListBookReviewsQuery;
use App\Entity\Book;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListBookReviewsHandler
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(ListBookReviewsQuery $query): array
    {
        $book = $this->entityManager->getRepository(Book::class)->find($query->bookId);
        
        if (!$book) {
            throw new NotFoundHttpException('Book not found');
        }

        $summary = $this->reviewRepository->getSummaryForBook($book);
        $reviews = $this->reviewRepository->findByBook($book);

        return [
            'summary' => $summary,
            'reviews' => $reviews,
            'userReview' => null,
        ];
    }
}
