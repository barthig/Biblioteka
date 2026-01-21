<?php
namespace App\Tests\Functional;

class RecommendationFeedbackControllerTest extends ApiTestCase
{
    public function testAddFeedbackRequiresAuthentication(): void
    {
        $book = $this->createBook('Suggested Book');
        $client = $this->createApiClient();
        $this->jsonRequest($client, 'POST', '/api/recommendation-feedback', [
            'bookId' => $book->getId(),
            'feedbackType' => 'dismiss'
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAddFeedbackValidatesType(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Suggested Book');

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/recommendation-feedback', [
            'bookId' => $book->getId(),
            'feedbackType' => 'invalid'
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testAddAndRemoveFeedback(): void
    {
        $user = $this->createUser('reader@example.com');
        $book = $this->createBook('Suggested Book');

        $client = $this->createAuthenticatedClient($user);
        $this->jsonRequest($client, 'POST', '/api/recommendation-feedback', [
            'bookId' => $book->getId(),
            'feedbackType' => 'dismiss'
        ]);

        $this->assertResponseStatusCodeSame(200);

        $this->sendRequest($client, 'DELETE', '/api/recommendation-feedback/' . $book->getId());
        $this->assertResponseStatusCodeSame(200);
    }
}
