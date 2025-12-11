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
        self::assertSame($book->getId(), $payload['book']['id']);

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
        $this->sendRequest($cancelClient, 'DELETE', '/api/reservations/' . $reservationData['id']);
        $this->assertResponseStatusCodeSame(204);

        $repo = $this->entityManager->getRepository(Reservation::class);
        $reservation = $repo->find($reservationData['id']);
        self::assertNotNull($reservation);
        self::assertSame(Reservation::STATUS_CANCELLED, $reservation->getStatus());
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
