<?php
namespace App\Tests\Service;

use App\Entity\RegistrationToken;
use App\Entity\User;
use App\Repository\RegistrationTokenRepository;
use App\Repository\UserRepository;
use App\Service\Book\OpenAIEmbeddingService;
use App\Service\RegistrationException;
use App\Service\Auth\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class RegistrationServiceTest extends TestCase
{
    public function testRegisterRejectsInvalidEmail(): void
    {
        $service = $this->createService();

        $this->expectException(RegistrationException::class);
        $service->register([
            'email' => 'invalid',
            'name' => 'Test User',
            'password' => 'Pass1234',
            'privacyConsent' => true
        ]);
    }

    public function testRegisterCreatesUserAndToken(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $users = $this->createMock(UserRepository::class);
        $tokens = $this->createMock(RegistrationTokenRepository::class);
        $embedding = $this->createMock(OpenAIEmbeddingService::class);

        $users->method('findOneBy')->willReturn(null);
        $embedding->expects($this->once())->method('getVector')->willReturn([0.1, 0.2]);

        $entityManager->expects($this->exactly(2))->method('persist')->with($this->callback(
            function ($entity) {
                return $entity instanceof User || $entity instanceof RegistrationToken;
            }
        ));
        $entityManager->expects($this->once())->method('flush');

        $service = new RegistrationService($entityManager, $users, $tokens, $embedding);
        $token = $service->register([
            'email' => 'USER@EXAMPLE.COM',
            'name' => 'Test User',
            'password' => 'Pass1234',
            'privacyConsent' => true,
            'tastePrompt' => 'fantasy'
        ]);

        $this->assertSame('user@example.com', $token->getUser()->getEmail());
        $this->assertFalse($token->getUser()->isVerified());
    }

    public function testVerifyMarksUserAndToken(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $users = $this->createMock(UserRepository::class);
        $tokens = $this->createMock(RegistrationTokenRepository::class);
        $embedding = $this->createMock(OpenAIEmbeddingService::class);

        $user = new User();
        $user->setEmail('user@example.com')->setName('User')->requireVerification();
        $token = new RegistrationToken($user, 'token', new \DateTimeImmutable('+1 day'));

        $tokens->method('findActiveByToken')->with('token')->willReturn($token);

        $entityManager->expects($this->atLeastOnce())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $service = new RegistrationService($entityManager, $users, $tokens, $embedding);
        $verified = $service->verify('token');

        $this->assertTrue($verified->isVerified());
        $this->assertTrue($token->isConsumed());
    }

    public function testVerifyRejectsExpiredToken(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $users = $this->createMock(UserRepository::class);
        $tokens = $this->createMock(RegistrationTokenRepository::class);
        $embedding = $this->createMock(OpenAIEmbeddingService::class);

        $user = new User();
        $token = new RegistrationToken($user, 'token', new \DateTimeImmutable('-1 day'));

        $tokens->method('findActiveByToken')->with('token')->willReturn($token);

        $service = new RegistrationService($entityManager, $users, $tokens, $embedding);

        $this->expectException(RegistrationException::class);
        $service->verify('token');
    }

    private function createService(): RegistrationService
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $users = $this->createMock(UserRepository::class);
        $tokens = $this->createMock(RegistrationTokenRepository::class);
        $embedding = $this->createMock(OpenAIEmbeddingService::class);
        $users->method('findOneBy')->willReturn(null);

        return new RegistrationService($entityManager, $users, $tokens, $embedding);
    }
}
