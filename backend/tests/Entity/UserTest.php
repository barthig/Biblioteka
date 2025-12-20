<?php
namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testTasteEmbeddingIsStoredAndReturned(): void
    {
        $user = new User();
        $embedding = [0.1, 0.2, 0.3];

        $user->setTasteEmbedding($embedding);

        $this->assertSame($embedding, $user->getTasteEmbedding());
    }

    public function testDefaultsAreInitialized(): void
    {
        $user = new User();

        $this->assertSame('standard', $user->getMembershipGroup());
        $this->assertSame(5, $user->getLoanLimit());
        $this->assertTrue($user->isNewsletterSubscribed());
    }
}
