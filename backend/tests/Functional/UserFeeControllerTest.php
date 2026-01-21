<?php
namespace App\Tests\Functional;

use App\Entity\Fine;

class UserFeeControllerTest extends ApiTestCase
{
    public function testListFeesRequiresAuthentication(): void
    {
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/me/fees');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testListFeesReturnsOnlyOutstandingForUser(): void
    {
        $user = $this->createUser('reader@example.com');
        $other = $this->createUser('other@example.com');
        $book1 = $this->createBook('Fee Book');
        $book2 = $this->createBook('Other Fee Book');

        $loan1 = $this->createLoan($user, $book1);
        $loan2 = $this->createLoan($user, $book1);
        $loan3 = $this->createLoan($other, $book2);

        $this->createFineForLoan($loan1, '5.00', false, 'Zaleglosc');
        $this->createFineForLoan($loan2, '8.00', true, 'Zaplacona kara');
        $this->createFineForLoan($loan3, '10.00', false, 'Cudza kara');

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'GET', '/api/me/fees');

        $this->assertResponseStatusCodeSame(200);
        $payload = $this->getJsonResponse($client);
        $this->assertArrayHasKey('data', $payload);
        $this->assertCount(1, $payload['data']);
    }

    public function testPayFeeRequiresAuthentication(): void
    {
        $user = $this->createUser('payer@example.com');
        $book = $this->createBook('Fee Book');
        $loan = $this->createLoan($user, $book);
        $fee = $this->createFineForLoan($loan, '12.00', false, 'Testowa kara');

        $client = $this->createApiClient();
        $this->sendRequest($client, 'POST', '/api/me/fees/' . $fee->getId() . '/pay');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUserCanPayOwnFee(): void
    {
        $user = $this->createUser('payer@example.com');
        $book = $this->createBook('Fee Book');
        $loan = $this->createLoan($user, $book);
        $fee = $this->createFineForLoan($loan, '12.00', false, 'Testowa kara');

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'POST', '/api/me/fees/' . $fee->getId() . '/pay');

        $this->assertResponseStatusCodeSame(200);
        $reloaded = $this->entityManager->getRepository(Fine::class)->find($fee->getId());
        $this->assertNotNull($reloaded);
        $this->assertTrue($reloaded->isPaid());
    }

    public function testUserCannotPayFeeBelongingToAnotherUser(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $book = $this->createBook('Fee Book');
        $loan = $this->createLoan($owner, $book);
        $fee = $this->createFineForLoan($loan, '9.00', false, 'Cudza kara');

        $client = $this->createAuthenticatedClient($other);
        $this->sendRequest($client, 'POST', '/api/me/fees/' . $fee->getId() . '/pay');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPayFeeAlreadyPaid(): void
    {
        $user = $this->createUser('payer@example.com');
        $book = $this->createBook('Fee Book');
        $loan = $this->createLoan($user, $book);
        $fee = $this->createFineForLoan($loan, '7.00', true, 'Zaplacona');

        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'POST', '/api/me/fees/' . $fee->getId() . '/pay');

        $this->assertResponseStatusCodeSame(400);
    }
}
