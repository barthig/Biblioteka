<?php
namespace App\Tests\Functional;

use App\Entity\Favorite;

class FavoriteControllerTest extends ApiTestCase
{
    public function testListFavoritesRequiresAuthentication(): void
    {
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/favorites');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAddFavoriteCreatesRecord(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Favorite Book');
        $client = $this->createAuthenticatedClient($user);

        $this->jsonRequest($client, 'POST', '/api/favorites', [
            'bookId' => $book->getId(),
        ]);

        $this->assertResponseStatusCodeSame(201);

        $favorite = $this->entityManager->getRepository(Favorite::class)->findOneBy([
            'user' => $user,
            'book' => $book,
        ]);
        self::assertNotNull($favorite);
    }

    public function testRemoveFavoriteByBookId(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Favorite Book');
        $favorite = (new Favorite())
            ->setUser($user)
            ->setBook($book);
        $this->entityManager->persist($favorite);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'DELETE', '/api/favorites/' . $book->getId());

        $this->assertResponseStatusCodeSame(204);
        $deleted = $this->entityManager->getRepository(Favorite::class)->find($favorite->getId());
        self::assertNull($deleted);
    }
}
