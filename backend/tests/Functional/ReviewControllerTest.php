<?php
namespace App\Tests\Functional;

use App\Entity\Rating;
use App\Entity\Review;

class ReviewControllerTest extends ApiTestCase
{
    public function testListReviewsReturnsData(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Reviewed Book');
        $rating = (new Rating())
            ->setUser($user)
            ->setBook($book)
            ->setRating(4)
            ->setReview('Nice');
        $this->entityManager->persist($rating);
        $this->entityManager->flush();

        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/books/' . $book->getId() . '/reviews');

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('reviews', $payload);
        $this->assertCount(1, $payload['reviews']);
    }

    public function testUpsertReviewRequiresAuthentication(): void
    {
        $book = $this->createBook('Reviewed Book');
        $client = $this->createApiClient();
        $this->jsonRequest($client, 'POST', '/api/books/' . $book->getId() . '/reviews', [
            'rating' => 5,
            'comment' => 'Great'
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpsertReviewCreatesReview(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Reviewed Book');

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/books/' . $book->getId() . '/reviews', [
            'rating' => 5,
            'comment' => 'Great'
        ]);

        $this->assertResponseStatusCodeSame(200);
        $review = $this->entityManager->getRepository(Review::class)->findOneBy([
            'user' => $user,
            'book' => $book
        ]);
        $this->assertNotNull($review);
        $this->assertSame(5, $review->getRating());
    }

    public function testDeleteReviewRequiresAuthentication(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Reviewed Book');
        $review = (new Review())
            ->setUser($user)
            ->setBook($book)
            ->setRating(4)
            ->setComment('Nice');
        $this->entityManager->persist($review);
        $this->entityManager->flush();

        $client = $this->createApiClient();
        $this->sendRequest($client, 'DELETE', '/api/books/' . $review->getId() . '/reviews');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteReviewRemovesReview(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Reviewed Book');
        $review = (new Review())
            ->setUser($user)
            ->setBook($book)
            ->setRating(4)
            ->setComment('Nice');
        $this->entityManager->persist($review);

        $rating = (new Rating())
            ->setUser($user)
            ->setBook($book)
            ->setRating(4)
            ->setReview('Nice');
        $this->entityManager->persist($rating);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'DELETE', '/api/books/' . $review->getId() . '/reviews');

        $this->assertResponseStatusCodeSame(204);
        $deleted = $this->entityManager->getRepository(Review::class)->find($review->getId());
        $this->assertNull($deleted);
    }
}
