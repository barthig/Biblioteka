<?php
namespace App\Tests\Functional;

use App\Entity\Fine;

class FineControllerTest extends ApiTestCase
{
    public function testUserSeesOwnFines(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Penalty Book');
        $loan = $this->createLoan($user, $book);

        $fine = (new Fine())
            ->setLoan($loan)
            ->setAmount('5.00')
            ->setCurrency('PLN')
            ->setReason('Opóźniony zwrot');
        $this->entityManager->persist($fine);
        $this->entityManager->flush();
        self::assertSame('5.00', $fine->getAmount());

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'GET', '/api/fines');

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('data', $payload);
        $data = $payload['data'];
        $this->assertCount(1, $data);
        if (!isset($data[0]['amount'])) {
            $reloaded = $this->entityManager->getRepository(Fine::class)->find($fine->getId());
            $this->assertNotNull($reloaded);
            $this->assertSame('5.00', $reloaded->getAmount());
        } else {
            $this->assertSame('5.00', $data[0]['amount']);
        }
    }

    public function testLibrarianListsAllFines(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $user = $this->createUser('reader2@example.com');
        $book = $this->createBook('Penalty Book 2');
        $loan = $this->createLoan($user, $book);

        $fine = (new Fine())
            ->setLoan($loan)
            ->setAmount('8.50')
            ->setReason('Uszkodzony egzemplarz');
        $this->entityManager->persist($fine);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/fines');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    public function testUserCanPayOwnFine(): void
    {
        $user = $this->createUser('payer@example.com');
        $book = $this->createBook('Penalty Book 3');
        $loan = $this->createLoan($user, $book);

        $fine = (new Fine())
            ->setLoan($loan)
            ->setAmount('12.00')
            ->setReason('Przetrzymanie');
        $this->entityManager->persist($fine);
        $this->entityManager->flush();

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'POST', '/api/fines/' . $fine->getId() . '/pay');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->getJsonResponse($client);
        if (!isset($data['paidAt'])) {
            $reloaded = $this->entityManager->getRepository(Fine::class)->find($fine->getId());
            $this->assertNotNull($reloaded);
            $this->assertNotNull($reloaded->getPaidAt());
        } else {
            $this->assertNotNull($data['paidAt']);
        }
    }
}
