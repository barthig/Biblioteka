<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testUsersListRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    // Additional tests should set the API_SECRET header and verify 200/404/400 cases
}
