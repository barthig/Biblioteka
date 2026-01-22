<?php
namespace App\Tests\Service;

use App\Entity\Book;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\CollectionRepository;
use App\Repository\FavoriteRepository;
use App\Repository\LoanRepository;
use App\Repository\RatingRepository;
use App\Repository\RecommendationFeedbackRepository;
use App\Service\PersonalizedRecommendationService;
use PHPUnit\Framework\TestCase;

class PersonalizedRecommendationServiceTest extends TestCase
{
    public function testFallbackGroupsForAnonymousUser(): void
    {
        $bookRepo = $this->createMock(BookRepository::class);
        $favoriteRepo = $this->createMock(FavoriteRepository::class);
        $loanRepo = $this->createMock(LoanRepository::class);
        $ratingRepo = $this->createMock(RatingRepository::class);
        $feedbackRepo = $this->createMock(RecommendationFeedbackRepository::class);
        $collectionRepo = $this->createMock(CollectionRepository::class);

        $collectionRepo->method('findFeatured')->willReturn([]);
        $bookRepo->method('findMostBorrowedBooks')->willReturn([]);

        $ageGroups = array_keys(Book::getAgeGroupDefinitions());
        $bookRepo->expects($this->exactly(count($ageGroups)))->method('findRecommendedByAgeGroup')->willReturn([]);

        $service = new PersonalizedRecommendationService(
            $bookRepo,
            $favoriteRepo,
            $loanRepo,
            $ratingRepo,
            $feedbackRepo,
            $collectionRepo
        );

        $groups = $service->getRecommendationsForUser(null, 2);
        $this->assertCount(count($ageGroups), $groups);
    }

    public function testAgeGroupHintForMember(): void
    {
        $bookRepo = $this->createMock(BookRepository::class);
        $favoriteRepo = $this->createMock(FavoriteRepository::class);
        $loanRepo = $this->createMock(LoanRepository::class);
        $ratingRepo = $this->createMock(RatingRepository::class);
        $feedbackRepo = $this->createMock(RecommendationFeedbackRepository::class);
        $collectionRepo = $this->createMock(CollectionRepository::class);

        $user = new User();
        $user->setMembershipGroup(User::GROUP_CHILD);

        $collectionRepo->method('findFeatured')->willReturn([]);
        $favoriteRepo->method('findByUser')->willReturn([]);
        $loanRepo->method('findRecentByUser')->willReturn([]);
        $ratingRepo->method('findHighlyRatedBooksByUser')->willReturn([]);
        $feedbackRepo->method('getDismissedBookIdsByUser')->willReturn([]);
        $bookRepo->method('findMostBorrowedBooks')->willReturn([]);

        $bookRepo->expects($this->once())
            ->method('findRecommendedByAgeGroup')
            ->with(Book::AGE_GROUP_EARLY_SCHOOL, 2, $this->anything())
            ->willReturn([]);

        $service = new PersonalizedRecommendationService(
            $bookRepo,
            $favoriteRepo,
            $loanRepo,
            $ratingRepo,
            $feedbackRepo,
            $collectionRepo
        );

        $groups = $service->getRecommendationsForUser($user, 2);
        $this->assertCount(1, $groups);
        $this->assertSame('age-group', $groups[0]['key']);
    }
}
