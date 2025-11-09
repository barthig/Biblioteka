<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;

class UserControllerTest extends WebTestCase
{
    public function testUsersListRequiresAuth(): void
    {
        /** @var AbstractBrowser $client */
        $client = static::createClient();
        $client->request('GET', '/api/users');

        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}