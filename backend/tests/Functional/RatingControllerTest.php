<?php
namespace App\Tests\Functional;

use App\Entity\Rating;

class RatingControllerTest extends ApiTestCase
{
    public function testListRatingsIncludesUserRating(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Rated Book');

        $rating = (new Rating())
            ->setUser($user)
            ->setBook($book)
            ->setRating(4)
            ->setReview('Solid read');
        $this->entityManager->persist($rating);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'GET', '/api/books/' . $book->getId() . '/ratings');

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('userRating', $payload);
        $this->assertSame($rating->getId(), $payload['userRating']['id']);
    }

    public function testRateBookRequiresAuthentication(): void
    {
        $book = $this->createBook('Rate Me');
        $client = $this->createApiClient();
        $this->jsonRequest($client, 'POST', '/api/books/' . $book->getId() . '/rate', [
            'rating' => 5,
            'review' => 'Great'
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRateBookCreatesRating(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Rate Me Too');

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/books/' . $book->getId() . '/rate', [
            'rating' => 5,
            'review' => 'Great'
        ]);

        $this->assertResponseStatusCodeSame(200);
        $rating = $this->entityManager->getRepository(Rating::class)->findOneBy([
            'user' => $user,
            'book' => $book
        ]);
        $this->assertNotNull($rating);
        $this->assertSame(5, $rating->getRating());
    }

    public function testDeleteRatingRequiresAuthentication(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Rate To Delete');
        $rating = (new Rating())
            ->setUser($user)
            ->setBook($book)
            ->setRating(3);
        $this->entityManager->persist($rating);
        $this->entityManager->flush();

        $client = $this->createApiClient();
        $this->sendRequest($client, 'DELETE', '/api/books/' . $book->getId() . '/ratings/' . $rating->getId());

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteRatingRemovesRating(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Rate To Delete');
        $rating = (new Rating())
            ->setUser($user)
            ->setBook($book)
            ->setRating(3);
        $this->entityManager->persist($rating);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'DELETE', '/api/books/' . $book->getId() . '/ratings/' . $rating->getId());

        $this->assertResponseStatusCodeSame(200);
        $reloaded = $this->entityManager->getRepository(Rating::class)->find($rating->getId());
        $this->assertNull($reloaded);
    }

    public function testUserRatingsRequiresAuthentication(): void
    {
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/users/me/ratings');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUserRatingsReturnsList(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Rated Book');
        $rating = (new Rating())
            ->setUser($user)
            ->setBook($book)
            ->setRating(4)
            ->setReview('Solid read');
        $this->entityManager->persist($rating);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'GET', '/api/users/me/ratings');

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('ratings', $payload);
        $this->assertCount(1, $payload['ratings']);
    }
}
