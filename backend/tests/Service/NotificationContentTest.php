<?php
namespace App\Tests\Service;

use App\Service\Notification\NotificationContent;
use PHPUnit\Framework\TestCase;

class NotificationContentTest extends TestCase
{
    public function testGettersReturnData(): void
    {
        $content = new NotificationContent('Subject', 'Text', '<p>Html</p>', ['email']);

        $this->assertSame('Subject', $content->getSubject());
        $this->assertSame('Text', $content->getTextBody());
        $this->assertSame('<p>Html</p>', $content->getHtmlBody());
        $this->assertSame(['email'], $content->getChannels());
    }
}
