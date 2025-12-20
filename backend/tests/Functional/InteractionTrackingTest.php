<?php
namespace App\Tests\Functional;

use App\Entity\UserBookInteraction;

class InteractionTrackingTest extends ApiTestCase
{
    public function testFavoriteCreatesInteraction(): void
    {
        $user = $this->createUser('favorite-tracker@example.com');
        $book = $this->createBook('Favorite Book');

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/favorites', [
            'bookId' => $book->getId(),
        ]);

        $this->assertResponseStatusCodeSame(201);

        $interaction = $this->entityManager->getRepository(UserBookInteraction::class)
            ->findOneBy(['user' => $user, 'book' => $book]);

        self::assertNotNull($interaction);
        self::assertSame(UserBookInteraction::TYPE_LIKED, $interaction->getType());
        self::assertNull($interaction->getRating());
    }

    public function testRatingCreatesInteraction(): void
    {
        $user = $this->createUser('rating-tracker@example.com');
        $book = $this->createBook('Rated Book');

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/books/' . $book->getId() . '/rate', [
            'rating' => 5,
            'review' => 'Great book',
        ]);

        $this->assertResponseStatusCodeSame(200);

        $interaction = $this->entityManager->getRepository(UserBookInteraction::class)
            ->findOneBy(['user' => $user, 'book' => $book]);

        self::assertNotNull($interaction);
        self::assertSame(UserBookInteraction::TYPE_LIKED, $interaction->getType());
        self::assertSame(5, $interaction->getRating());
    }

    public function testLoanCreatesInteraction(): void
    {
        $user = $this->createUser('loan-tracker@example.com');
        $book = $this->createBook('Loaned Book');

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/loans', [
            'userId' => $user->getId(),
            'bookId' => $book->getId(),
        ]);

        $this->assertResponseStatusCodeSame(201);

        $interaction = $this->entityManager->getRepository(UserBookInteraction::class)
            ->findOneBy(['user' => $user, 'book' => $book]);

        self::assertNotNull($interaction);
        self::assertSame(UserBookInteraction::TYPE_READ, $interaction->getType());
        self::assertNull($interaction->getRating());
    }
}
