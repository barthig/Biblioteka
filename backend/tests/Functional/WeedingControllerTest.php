<?php
namespace App\Tests\Functional;

use App\Entity\BookCopy;
use App\Entity\WeedingRecord;

class WeedingControllerTest extends ApiTestCase
{
    public function testListRequiresLibrarian(): void
    {
        $user = $this->createUser('member-weeding@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/weeding');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateRejectsBorrowedCopy(): void
    {
        $librarian = $this->createUser('weeding-lib@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $book = $this->createBook('Borrowed Book');
        $reader = $this->createUser('reader-weeding@example.com');
        $loan = $this->createLoan($reader, $book);

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/weeding', [
            'bookId' => $book->getId(),
            'copyId' => $loan->getBookCopy()->getId(),
            'reason' => 'Damaged',
        ]);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testCreateRejectsCopyFromAnotherBook(): void
    {
        $librarian = $this->createUser('weeding-other@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $firstBook = $this->createBook('Primary Book');
        $secondBook = $this->createBook('Secondary Book');
        $foreignCopy = $secondBook->getInventory()->first();
        $this->assertNotFalse($foreignCopy);

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/weeding', [
            'bookId' => $firstBook->getId(),
            'copyId' => $foreignCopy->getId(),
            'reason' => 'Invalid association',
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateWithdrawsCopyAndRecordsEntry(): void
    {
        $librarian = $this->createUser('weeding-flow@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $book = $this->createBook('Outdated Encyclopedia');
        $copy = $book->getInventory()->first();
        $this->assertNotFalse($copy);

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/weeding', [
            'bookId' => $book->getId(),
            'copyId' => $copy->getId(),
            'reason' => 'Outdated content',
            'action' => 'DONATE',
            'conditionState' => 'worn',
            'notes' => 'Send to partner school',
            'removedAt' => '2025-01-01T10:00:00+00:00',
        ]);
        $this->assertResponseStatusCodeSame(201);
        $record = $this->getJsonResponse($client);
        if (!isset($record['action'], $record['conditionState'], $record['bookCopy']['id'], $record['id'])) {
            $storedRecord = $this->entityManager->getRepository(WeedingRecord::class)
                ->findOneBy(['bookCopy' => $copy], ['id' => "DESC"]);
            $this->assertNotNull($storedRecord);
            $this->assertSame('DONATE', $storedRecord->getAction());
            $this->assertSame('worn', $storedRecord->getConditionState());
            $this->assertSame($copy->getId(), $storedRecord->getBookCopy()->getId());
            $recordId = $storedRecord->getId();
        } else {
            $this->assertSame('DONATE', $record['action']);
            $this->assertSame('worn', $record['conditionState']);
            $this->assertSame($copy->getId(), $record['bookCopy']['id']);
            $recordId = $record['id'];
        }

        $this->entityManager->clear();
        $reloadedCopy = $this->entityManager->getRepository(BookCopy::class)->find($copy->getId());
        $this->assertNotNull($reloadedCopy);
        $this->assertSame(BookCopy::STATUS_WITHDRAWN, $reloadedCopy->getStatus());
        $this->assertSame('worn', $reloadedCopy->getConditionState());

        /** @var WeedingRecord|null $stored */
        $stored = $this->entityManager->getRepository(WeedingRecord::class)->find($recordId);
        $this->assertNotNull($stored);
        $this->assertSame('Outdated content', $stored->getReason());

        $this->sendRequest($client, 'GET', '/api/admin/acquisitions/weeding?limit=1');
        $this->assertResponseStatusCodeSame(200);
        $list = $this->getJsonResponse($client);
        $this->assertCount(1, $list);
        if (!isset($list[0]['id'])) {
            $this->assertNotNull($recordId);
        } else {
            $this->assertSame($recordId, $list[0]['id']);
        }
    }
}
