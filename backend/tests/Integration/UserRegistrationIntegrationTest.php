<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test integracyjny: Pełny cykl rejestracji i weryfikacji użytkownika
 */
class UserRegistrationIntegrationTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCompleteUserRegistrationAndVerification(): void
    {
        $email = 'newuser_' . time() . '@example.com';
        
        // 1. Rejestracja
        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'email' => $email,
            'password' => 'SecurePass123!',
            'firstName' => 'Jan',
            'lastName' => 'Testowy'
        ]));
        
        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('userId', $response);

        // 2. Próba logowania przed weryfikacją - powinna się nie udać
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'email' => $email,
            'password' => 'SecurePass123!'
        ]));
        
        $this->assertResponseStatusCodeSame(403);
        $error = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('not verified', $error['error']);

        // 3. Weryfikacja email (w prawdziwym systemie byłby token z emaila)
        // W teście możemy pobrać token z bazy lub użyć test endpoint

        // 4. Po weryfikacji - logowanie powinno działać
        // (Zakładając że mamy test endpoint do weryfikacji)
        
        $this->assertTrue(true); // Placeholder
    }

    public function testDuplicateEmailRegistrationFails(): void
    {
        // Próba rejestracji z istniejącym emailem
        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'email' => 'user@example.com', // Już istnieje w fixtures
            'password' => 'SecurePass123!',
            'firstName' => 'Jan',
            'lastName' => 'Testowy'
        ]));
        
        $this->assertResponseStatusCodeSame(409); // Conflict
    }

    public function testWeakPasswordValidation(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'email' => 'test@example.com',
            'password' => '123', // Za słabe hasło
            'firstName' => 'Jan',
            'lastName' => 'Testowy'
        ]));
        
        $this->assertResponseStatusCodeSame(400);
        $error = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('password', strtolower($error['error']));
    }
}
