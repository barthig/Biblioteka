<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test integracyjny: Pełny cykl wypożyczenia książki
 */
class LoanIntegrationTest extends WebTestCase
{
    private $client;
    private $token;
    private $librarianToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        // Login jako użytkownik
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'email' => 'user@example.com',
            'password' => 'password123'
        ]));
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $data['token'];

        // Login jako bibliotekarz
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'email' => 'librarian@example.com',
            'password' => 'librarian123'
        ]));
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->librarianToken = $data['token'];
    }

    public function testCompleteBookLoanFlow(): void
    {
        // 1. Użytkownik wyszukuje książkę
        $this->client->request('GET', '/api/books?search=Test', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ]);
        
        $this->assertResponseIsSuccessful();
        $books = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($books['data']);
        $bookId = $books['data'][0]['id'];

        // 2. Użytkownik sprawdza dostępność
        $this->client->request('GET', "/api/books/{$bookId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ]);
        
        $this->assertResponseIsSuccessful();
        $book = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertGreaterThan(0, $book['availableCopies']);
        $copyId = $book['copies'][0]['id'];

        // 3. Bibliotekarz tworzy wypożyczenie
        $this->client->request('POST', '/api/loans', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'bookCopyId' => $copyId,
            'userId' => 1 // ID użytkownika
        ]));
        
        $this->assertResponseStatusCodeSame(201);
        $loan = json_decode($this->client->getResponse()->getContent(), true);
        $loanId = $loan['id'];
        $this->assertNotNull($loan['dueDate']);

        // 4. Użytkownik sprawdza swoje wypożyczenia
        $this->client->request('GET', '/api/loans', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ]);
        
        $this->assertResponseIsSuccessful();
        $loans = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($loans['data']);
        $this->assertEquals($loanId, $loans['data'][0]['id']);

        // 5. Użytkownik przedłuża wypożyczenie
        $this->client->request('POST', "/api/loans/{$loanId}/renew", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ]);
        
        $this->assertResponseIsSuccessful();
        $renewed = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1, $renewed['renewalCount']);

        // 6. Bibliotekarz zwraca książkę
        $this->client->request('POST', "/api/loans/{$loanId}/return", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->librarianToken,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ]);
        
        $this->assertResponseIsSuccessful();

        // 7. Weryfikacja - książka znów dostępna
        $this->client->request('GET', "/api/books/{$bookId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ]);
        
        $this->assertResponseIsSuccessful();
        $book = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertGreaterThan(0, $book['availableCopies']);
    }

    public function testReservationToLoanFlow(): void
    {
        // 1. Wszystkie egzemplarze wypożyczone
        $bookId = 1;
        
        // 2. Użytkownik tworzy rezerwację
        $this->client->request('POST', '/api/reservations', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ], json_encode([
            'bookId' => $bookId
        ]));
        
        $this->assertResponseStatusCodeSame(201);
        $reservation = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('queuePosition', $reservation);

        // 3. Książka zostaje zwrócona (w prawdziwym scenariuszu)
        // Rezerwacja powinna się zmienić na ready
        
        // 4. Użytkownik sprawdza rezerwacje
        $this->client->request('GET', '/api/reservations', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'HTTP_X_API_SECRET' => $_ENV['API_SECRET']
        ]);
        
        $this->assertResponseIsSuccessful();
    }

    public function testOverdueLoanCreatesfine(): void
    {
        // Ten test wymaga mockowania czasu lub czekania
        // W prawdziwym środowisku można to testować za pomocą CLI command
        $this->markTestSkipped('Requires time manipulation or cron job simulation');
    }
}
