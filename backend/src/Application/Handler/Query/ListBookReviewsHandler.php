<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Review\ListBookReviewsQuery;
use App\Entity\Book;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListBookReviewsHandler
{
    public function __construct(
        private readonly RatingRepository $ratingRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(ListBookReviewsQuery $query): array
    {
        $book = $this->entityManager->getRepository(Book::class)->find($query->bookId);
        
        if (!$book) {
            throw new NotFoundHttpException('Book not found');
        }

        $summary = [
            'average' => $this->ratingRepository->getAverageRatingForBook($book->getId()),
            'total' => $this->ratingRepository->getRatingCountForBook($book->getId()),
        ];
        $ratings = $this->ratingRepository->findByBook($book);

        $reviews = array_map(static function ($rating): array {
            $user = $rating->getUser();
            return [
                'id' => $rating->getId(),
                'rating' => $rating->getRating(),
                'comment' => $rating->getReview(),
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                ],
                'createdAt' => $rating->getCreatedAt()->format(DATE_ATOM),
                'updatedAt' => $rating->getUpdatedAt()?->format(DATE_ATOM),
            ];
        }, $ratings);

        return [
            'summary' => $summary,
            'reviews' => $reviews,
            'userReview' => null,
        ];
    }
}
