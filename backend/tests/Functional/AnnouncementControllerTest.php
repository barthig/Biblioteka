<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AnnouncementControllerTest extends WebTestCase
{
    private $client;
    private $librarianToken;
    private $userToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->librarianToken = 'test_librarian_token';
        $this->userToken = 'test_user_token';
    }

    public function testListAnnouncementsAsPublicUser(): void
    {
        $this->client->request('GET', '/api/announcements');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testListAnnouncementsWithPagination(): void
    {
        $this->client->request('GET', '/api/announcements?page=1&limit=10');

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('page', $responseData);
    }

    public function testListAnnouncementsFilterByStatus(): void
    {
        $this->client->request('GET', '/api/announcements?status=published');

        $this->assertResponseIsSuccessful();
    }

    public function testGetAnnouncementById(): void
    {
        $this->client->request('GET', '/api/announcements/1');

        // May return 200 or 404 depending on data
        $this->assertContains(
            $this->client->getResponse()->getStatusCode(),
            [Response::HTTP_OK, Response::HTTP_NOT_FOUND]
        );
    }

    public function testCreateAnnouncementRequiresLibrarianRole(): void
    {
        $this->client->request(
            'POST',
            '/api/announcements',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->userToken,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'title' => 'Test Announcement',
                'content' => 'Test Content'
            ])
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateAnnouncementAsLibrarian(): void
    {
        $this->client->request(
            'POST',
            '/api/announcements',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'title' => 'New Library Announcement',
                'content' => 'This is important information for all library users.',
                'type' => 'info',
                'isPinned' => false,
                'showOnHomepage' => true,
                'targetAudience' => ['all']
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('New Library Announcement', $responseData['title']);
        $this->assertEquals('draft', $responseData['status']);
    }

    public function testCreateAnnouncementValidatesRequiredFields(): void
    {
        $this->client->request(
            'POST',
            '/api/announcements',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'title' => '', // Invalid: empty title
                'content' => ''
            ])
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateAnnouncementAsLibrarian(): void
    {
        // First create an announcement
        $this->client->request(
            'POST',
            '/api/announcements',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'title' => 'Original Title',
                'content' => 'Original Content'
            ])
        );

        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $announcementId = $createResponse['id'];

        // Now update it
        $this->client->request(
            'PUT',
            "/api/announcements/{$announcementId}",
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'title' => 'Updated Title',
                'content' => 'Updated Content'
            ])
        );

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated Title', $responseData['title']);
    }

    public function testPublishAnnouncementChangesStatus(): void
    {
        // Create draft announcement
        $this->client->request(
            'POST',
            '/api/announcements',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'title' => 'To Publish',
                'content' => 'Content'
            ])
        );

        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $announcementId = $createResponse['id'];

        // Publish it
        $this->client->request(
            'POST',
            "/api/announcements/{$announcementId}/publish",
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken]
        );

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('published', $responseData['status']);
        $this->assertNotNull($responseData['publishedAt']);
    }

    public function testArchiveAnnouncementChangesStatus(): void
    {
        // Create and publish announcement
        $this->client->request(
            'POST',
            '/api/announcements',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'title' => 'To Archive',
                'content' => 'Content'
            ])
        );

        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $announcementId = $createResponse['id'];

        // Archive it
        $this->client->request(
            'POST',
            "/api/announcements/{$announcementId}/archive",
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken]
        );

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('archived', $responseData['status']);
    }

    public function testDeleteAnnouncement(): void
    {
        // Create announcement
        $this->client->request(
            'POST',
            '/api/announcements',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'title' => 'To Delete',
                'content' => 'Content'
            ])
        );

        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $announcementId = $createResponse['id'];

        // Delete it
        $this->client->request(
            'DELETE',
            "/api/announcements/{$announcementId}",
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken]
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }
}
