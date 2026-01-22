<?php
namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\UserBookInteractionRepository;
use App\Service\RecommendationService;
use PHPUnit\Framework\TestCase;

class RecommendationServiceTest extends TestCase
{
    public function testReturnsPopularWhenNoVector(): void
    {
        $bookRepo = $this->createMock(BookRepository::class);
        $interactionRepo = $this->createMock(UserBookInteractionRepository::class);
        $user = new User();

        $interactionRepo->expects($this->once())->method('findBookIdsByUser')->with($user)->willReturn([1, 2]);
        $interactionRepo->expects($this->once())->method('findLikedInteractions')->with($user)->willReturn([]);
        $bookRepo->expects($this->once())->method('findPopularBooks')->with(3, [1, 2])->willReturn([]);

        $service = new RecommendationService($bookRepo, $interactionRepo);
        $result = $service->getPersonalizedRecommendations($user, 3);

        $this->assertSame('not_enough_data', $result['status']);
    }

    public function testReturnsSimilarWhenTasteEmbeddingExists(): void
    {
        $bookRepo = $this->createMock(BookRepository::class);
        $interactionRepo = $this->createMock(UserBookInteractionRepository::class);
        $user = new User();
        $user->setTasteEmbedding([0.1, 0.2]);

        $interactionRepo->expects($this->once())->method('findBookIdsByUser')->with($user)->willReturn([]);
        $interactionRepo->expects($this->once())->method('findLikedInteractions')->with($user)->willReturn([]);
        $bookRepo->expects($this->once())->method('findSimilarBooksExcluding')->with([0.1, 0.2], [], 5)->willReturn([]);

        $service = new RecommendationService($bookRepo, $interactionRepo);
        $result = $service->getPersonalizedRecommendations($user, 5);

        $this->assertSame('ok', $result['status']);
    }
}
