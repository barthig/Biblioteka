<?php
namespace App\Tests\Entity;

use App\Entity\Announcement;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class AnnouncementTest extends TestCase
{
    private function createUser(array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setName('Test User');
        $user->setRoles($roles);
        $user->setPassword('test');
        return $user;
    }

    public function testAnnouncementCreation(): void
    {
        $user = $this->createUser(['ROLE_LIBRARIAN']);
        
        $announcement = new Announcement();
        $announcement->setTitle('Test Announcement');
        $announcement->setContent('Test content');
        $announcement->setCreatedBy($user);

        $this->assertEquals('Test Announcement', $announcement->getTitle());
        $this->assertEquals('Test content', $announcement->getContent());
        $this->assertEquals('draft', $announcement->getStatus());
        $this->assertEquals('info', $announcement->getType());
        $this->assertFalse($announcement->isPinned());
        $this->assertTrue($announcement->isShowOnHomepage());
    }

    public function testPublishAnnouncement(): void
    {
        $user = $this->createUser(['ROLE_LIBRARIAN']);
        
        $announcement = new Announcement();
        $announcement->setTitle('Test');
        $announcement->setContent('Content');
        $announcement->setCreatedBy($user);

        $this->assertEquals('draft', $announcement->getStatus());
        $this->assertNull($announcement->getPublishedAt());

        $announcement->publish();

        $this->assertEquals('published', $announcement->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $announcement->getPublishedAt());
    }

    public function testArchiveAnnouncement(): void
    {
        $user = $this->createUser(['ROLE_LIBRARIAN']);
        
        $announcement = new Announcement();
        $announcement->setTitle('Test');
        $announcement->setContent('Content');
        $announcement->setCreatedBy($user);
        $announcement->publish();

        $this->assertEquals('published', $announcement->getStatus());

        $announcement->archive();

        $this->assertEquals('archived', $announcement->getStatus());
    }

    public function testIsActiveWhenPublished(): void
    {
        $user = $this->createUser(['ROLE_LIBRARIAN']);
        
        $announcement = new Announcement();
        $announcement->setTitle('Test');
        $announcement->setContent('Content');
        $announcement->setCreatedBy($user);

        $this->assertFalse($announcement->isActive());

        $announcement->publish();

        $this->assertTrue($announcement->isActive());
    }

    public function testIsNotActiveWhenExpired(): void
    {
        $user = $this->createUser(['ROLE_LIBRARIAN']);
        
        $announcement = new Announcement();
        $announcement->setTitle('Test');
        $announcement->setContent('Content');
        $announcement->setCreatedBy($user);
        $announcement->setExpiresAt((new \DateTimeImmutable())->modify('-1 day'));
        $announcement->publish();

        $this->assertFalse($announcement->isActive());
    }

    public function testIsNotActiveWhenNotYetPublishedDate(): void
    {
        $user = $this->createUser(['ROLE_LIBRARIAN']);
        
        $announcement = new Announcement();
        $announcement->setTitle('Test');
        $announcement->setContent('Content');
        $announcement->setCreatedBy($user);
        $announcement->publish();
        $announcement->setPublishedAt((new \DateTimeImmutable())->modify('+1 day'));

        $this->assertFalse($announcement->isActive());
    }

    public function testIsVisibleForAllUsers(): void
    {
        $admin = $this->createUser(['ROLE_LIBRARIAN']);
        $regularUser = $this->createUser(['ROLE_USER']);
        
        $announcement = new Announcement();
        $announcement->setTitle('Test');
        $announcement->setContent('Content');
        $announcement->setCreatedBy($admin);
        $announcement->setTargetAudience(['all']);
        $announcement->publish();

        $this->assertTrue($announcement->isVisibleForUser($regularUser));
        $this->assertTrue($announcement->isVisibleForUser($admin));
        $this->assertTrue($announcement->isVisibleForUser(null));
    }

    public function testIsVisibleOnlyForLibrarians(): void
    {
        $admin = $this->createUser(['ROLE_LIBRARIAN']);
        $regularUser = $this->createUser(['ROLE_USER']);
        
        $announcement = new Announcement();
        $announcement->setTitle('Test');
        $announcement->setContent('Content');
        $announcement->setCreatedBy($admin);
        $announcement->setTargetAudience(['librarians']);
        $announcement->publish();

        $this->assertFalse($announcement->isVisibleForUser($regularUser));
        $this->assertTrue($announcement->isVisibleForUser($admin));
        $this->assertFalse($announcement->isVisibleForUser(null));
    }

    public function testIsVisibleOnlyForUsers(): void
    {
        $admin = $this->createUser(['ROLE_LIBRARIAN']);
        $regularUser = $this->createUser(['ROLE_USER']);
        
        $announcement = new Announcement();
        $announcement->setTitle('Test');
        $announcement->setContent('Content');
        $announcement->setCreatedBy($admin);
        $announcement->setTargetAudience(['users']);
        $announcement->publish();

        $this->assertTrue($announcement->isVisibleForUser($regularUser));
        $this->assertFalse($announcement->isVisibleForUser($admin));
        $this->assertFalse($announcement->isVisibleForUser(null));
    }

    public function testIsNotVisibleWhenNotActive(): void
    {
        $admin = $this->createUser(['ROLE_LIBRARIAN']);
        $regularUser = $this->createUser(['ROLE_USER']);
        
        $announcement = new Announcement();
        $announcement->setTitle('Test');
        $announcement->setContent('Content');
        $announcement->setCreatedBy($admin);
        $announcement->setTargetAudience(['all']);
        // Nie publikuj

        $this->assertFalse($announcement->isVisibleForUser($regularUser));
        $this->assertFalse($announcement->isVisibleForUser($admin));
    }
}
