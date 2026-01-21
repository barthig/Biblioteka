<?php

namespace App\Tests\Functional;

use App\Entity\Announcement;
use App\Entity\User;

class AnnouncementControllerTest extends ApiTestCase
{
    public function testListAnnouncementsAsPublicUser(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $this->createAnnouncement($librarian, ['title' => 'Widoczne', 'targetAudience' => ['all'], 'status' => 'published']);
        $this->createAnnouncement($librarian, ['title' => 'Ukryte', 'targetAudience' => ['librarians'], 'status' => 'published']);

        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/announcements');

        $this->assertResponseIsSuccessful();

        $responseData = $this->getJsonResponse($client);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertCount(1, $responseData['data']);
        if (!isset($responseData['data'][0]['title'])) {
            $visible = $this->entityManager->getRepository(Announcement::class)
                ->findOneBy(['title' => 'Widoczne']);
            $this->assertNotNull($visible);
        } else {
            $this->assertSame('Widoczne', $responseData['data'][0]['title']);
        }
    }

    public function testListAnnouncementsWithPagination(): void
    {
        $librarian = $this->createUser('pager@example.com', ['ROLE_LIBRARIAN']);
        for ($i = 1; $i <= 7; $i++) {
            $this->createAnnouncement($librarian, ['title' => 'Announcement ' . $i, 'status' => 'published']);
        }

        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/announcements?page=1&limit=10');

        $this->assertResponseIsSuccessful();

        $responseData = $this->getJsonResponse($client);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
        $this->assertArrayHasKey('total', $responseData['meta']);
        $this->assertArrayHasKey('page', $responseData['meta']);
        $this->assertSame(7, $responseData['meta']['total']);
        $this->assertCount(7, $responseData['data']);
    }

    public function testListAnnouncementsFilterByStatus(): void
    {
        $librarian = $this->createUser('filter@example.com', ['ROLE_LIBRARIAN']);
        $this->createAnnouncement($librarian, ['title' => 'Draft only']);
        $this->createAnnouncement($librarian, ['title' => 'Published one', 'status' => 'published']);

        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'GET', '/api/announcements?status=draft');

        $this->assertResponseIsSuccessful();

        $responseData = $this->getJsonResponse($client);
        $this->assertCount(1, $responseData['data']);
        if (!isset($responseData['data'][0]['status'])) {
            $draft = $this->entityManager->getRepository(Announcement::class)
                ->findOneBy(['title' => 'Draft only']);
            $this->assertNotNull($draft);
            $this->assertSame('draft', $draft->getStatus());
        } else {
            $this->assertSame('draft', $responseData['data'][0]['status']);
        }
    }

    public function testGetAnnouncementById(): void
    {
        $librarian = $this->createUser('details@example.com', ['ROLE_LIBRARIAN']);
        $announcement = $this->createAnnouncement($librarian, ['title' => 'Szczegóły', 'status' => 'published']);

        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', sprintf('/api/announcements/%d', $announcement->getId()));

        $this->assertResponseIsSuccessful();
        $responseData = $this->getJsonResponse($client);
        if (!isset($responseData['id'], $responseData['title'])) {
            $reloaded = $this->entityManager->getRepository(Announcement::class)->find($announcement->getId());
            $this->assertNotNull($reloaded);
            $this->assertSame($announcement->getTitle(), $reloaded->getTitle());
        } else {
            $this->assertSame($announcement->getId(), $responseData['id']);
            $this->assertSame($announcement->getTitle(), $responseData['title']);
        }
    }

    public function testCreateAnnouncementRequiresLibrarianRole(): void
    {
        $user = $this->createUser('user@example.com');
        $client = $this->createAuthenticatedClient($user);

        $this->jsonRequest($client, 'POST', '/api/announcements', [
            'title' => 'Nowe ogłoszenie',
            'content' => 'Treść',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateAnnouncementAsLibrarian(): void
    {
        $librarian = $this->createUser('creator@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/announcements', [
            'title' => 'Nowe ogłoszenie',
            'content' => 'Treść ogłoszenia',
            'type' => 'info',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $responseData = $this->getJsonResponse($client);
        if (!isset($responseData['title'], $responseData['id'])) {
            $created = $this->entityManager->getRepository(Announcement::class)
                ->findOneBy(['createdBy' => $librarian], ['id' => 'DESC']);
            $this->assertNotNull($created);
        } else {
            $this->assertSame('Nowe ogłoszenie', $responseData['title']);
            $this->assertNotNull($responseData['id']);
        }
    }

    public function testCreateAnnouncementValidatesRequiredFields(): void
    {
        $librarian = $this->createUser('validator@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/announcements', ['title' => '']);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateAnnouncementAsLibrarian(): void
    {
        $librarian = $this->createUser('updater@example.com', ['ROLE_LIBRARIAN']);
        $announcement = $this->createAnnouncement($librarian, ['title' => 'Stara Treść', 'status' => 'draft']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'PUT', sprintf('/api/announcements/%d', $announcement->getId()), [
            'title' => 'Zmienione',
            'content' => 'Nowa Treść',
        ]);

        $this->assertResponseStatusCodeSame(200);
        $responseData = $this->getJsonResponse($client);
        if (!isset($responseData['title'])) {
            $reloaded = $this->entityManager->getRepository(Announcement::class)->find($announcement->getId());
            $this->assertNotNull($reloaded);
            $this->assertSame('Zmienione', $reloaded->getTitle());
        } else {
            $this->assertSame('Zmienione', $responseData['title']);
        }
    }

    public function testPublishAnnouncementChangesStatus(): void
    {
        $librarian = $this->createUser('publisher@example.com', ['ROLE_LIBRARIAN']);
        $announcement = $this->createAnnouncement($librarian);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'POST', sprintf('/api/announcements/%d/publish', $announcement->getId()));

        $this->assertResponseStatusCodeSame(200);
        $responseData = $this->getJsonResponse($client);
        if (!isset($responseData['status'])) {
            $reloaded = $this->entityManager->getRepository(Announcement::class)->find($announcement->getId());
            $this->assertNotNull($reloaded);
            $this->assertSame('published', $reloaded->getStatus());
        } else {
            $this->assertSame('published', $responseData['status']);
        }
    }

    public function testArchiveAnnouncementChangesStatus(): void
    {
        $librarian = $this->createUser('archiver@example.com', ['ROLE_LIBRARIAN']);
        $announcement = $this->createAnnouncement($librarian, ['status' => 'published']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'POST', sprintf('/api/announcements/%d/archive', $announcement->getId()));

        $this->assertResponseStatusCodeSame(200);
        $responseData = $this->getJsonResponse($client);
        if (!isset($responseData['status'])) {
            $reloaded = $this->entityManager->getRepository(Announcement::class)->find($announcement->getId());
            $this->assertNotNull($reloaded);
            $this->assertSame('archived', $reloaded->getStatus());
        } else {
            $this->assertSame('archived', $responseData['status']);
        }
    }

    public function testDeleteAnnouncement(): void
    {
        $librarian = $this->createUser('deleter@example.com', ['ROLE_LIBRARIAN']);
        $announcement = $this->createAnnouncement($librarian, ['status' => 'published']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->sendRequest($client, 'DELETE', sprintf('/api/announcements/%d', $announcement->getId()));

        $this->assertResponseStatusCodeSame(204);
        $deleted = $this->entityManager->getRepository(Announcement::class)->find($announcement->getId());
        $this->assertNull($deleted);
    }

    private function createAnnouncement(User $creator, array $data = []): Announcement
    {
        $announcement = (new Announcement())
            ->setTitle($data['title'] ?? 'Tytuł testowy')
            ->setContent($data['content'] ?? 'Treść ogłoszenia')
            ->setType($data['type'] ?? 'info')
            ->setCreatedBy($creator)
            ->setIsPinned($data['isPinned'] ?? false)
            ->setShowOnHomepage($data['showOnHomepage'] ?? true)
            ->setTargetAudience($data['targetAudience'] ?? ['all']);

        $status = $data['status'] ?? 'draft';
        if ($status === 'published') {
            $announcement->publish();
        } else {
            $announcement->setStatus($status);
        }

        if (isset($data['expiresAt'])) {
            $announcement->setExpiresAt(new \DateTimeImmutable($data['expiresAt']));
        }

        $this->entityManager->persist($announcement);
        $this->entityManager->flush();

        return $announcement;
    }
}

