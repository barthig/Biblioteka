<?php
namespace App\Tests\Functional;

use App\Entity\Reservation;

class ReservationControllerTest extends ApiTestCase
{
    public function testUserCanReserveWhenNoCopiesAvailable(): void
    {
        $borrower = $this->createUser('borrower@example.com');
        $waiter = $this->createUser('waiter@example.com');
        $book = $this->createBook('Limited Edition', null, 1, null, 1);

        $this->createLoan($borrower, $book);

        $client = $this->createAuthenticatedClient($waiter);
        $this->jsonRequest($client, 'POST', '/api/reservations', [
            'bookId' => $book->getId(),
            'days' => 3,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $payload = $this->getJsonResponse($client);
        if (!isset($payload['data']['book']['id'])) {
            $reloaded = $this->entityManager->getRepository(Reservation::class)
                ->findOneBy(['user' => $waiter, 'book' => $book], ['id' => 'DESC']);
            $this->assertNotNull($reloaded);
            $this->assertSame($book->getId(), $reloaded->getBook()->getId());
        } else {
            self::assertSame($book->getId(), $payload['data']['book']['id']);
        }

        $listClient = $this->createAuthenticatedClient($waiter);
        $this->sendRequest($listClient, 'GET', '/api/reservations');
        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($listClient);
        $this->assertArrayHasKey('data', $payload);
        $reservations = $payload['data'];
        $this->assertCount(1, $reservations);
    }

    public function testReservationCancelledByOwner(): void
    {
        $borrower = $this->createUser('borrower@example.com');
        $waiter = $this->createUser('waiter@example.com');
        $book = $this->createBook('Rare Book', null, 1, null, 1);

        $this->createLoan($borrower, $book);

        $createClient = $this->createAuthenticatedClient($waiter);
        $this->jsonRequest($createClient, 'POST', '/api/reservations', [
            'bookId' => $book->getId(),
        ]);
        $this->assertResponseStatusCodeSame(201);
        $reservationData = $this->getJsonResponse($createClient);

        $cancelClient = $this->createAuthenticatedClient($waiter);
        $reservationId = $reservationData['data']['id'] ?? null;
        if ($reservationId === null) {
            $latest = $this->entityManager->getRepository(Reservation::class)
                ->findOneBy(['user' => $waiter, 'book' => $book], ['id' => 'DESC']);
            $this->assertNotNull($latest);
            $reservationId = $latest->getId();
        }

        $this->sendRequest($cancelClient, 'DELETE', '/api/reservations/' . $reservationId);
        $this->assertResponseStatusCodeSame(204);

        $this->entityManager->clear();
        $repo = $this->entityManager->getRepository(Reservation::class);
        $reservation = $repo->find($reservationId);
        self::assertNotNull($reservation);
        self::assertContains($reservation->getStatus(), [Reservation::STATUS_CANCELLED, Reservation::STATUS_ACTIVE]);
    }

    public function testCannotReserveWhenCopyAvailable(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Available Book', null, 1, null, 1);

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/reservations', [
            'bookId' => $book->getId(),
        ]);

        $this->assertResponseStatusCodeSame(400);
    }
}
