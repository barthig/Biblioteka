<?php

namespace App\Tests\Application\Command;

use App\Application\Command\Announcement\PublishAnnouncementCommand;
use PHPUnit\Framework\TestCase;

class PublishAnnouncementCommandTest extends TestCase
{
    public function testConstructorSetsAnnouncementId(): void
    {
        $command = new PublishAnnouncementCommand(10);

        $this->assertEquals(10, $command->id);
    }

    public function testMultipleInstancesAreIndependent(): void
    {
        $command1 = new PublishAnnouncementCommand(1);
        $command2 = new PublishAnnouncementCommand(2);

        $this->assertEquals(1, $command1->id);
        $this->assertEquals(2, $command2->id);
        $this->assertNotEquals($command1->id, $command2->id);
    }
}
