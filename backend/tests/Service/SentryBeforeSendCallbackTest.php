<?php
namespace App\Tests\Service;

use App\Service\System\SentryBeforeSendCallback;
use PHPUnit\Framework\TestCase;

class SentryBeforeSendCallbackTest extends TestCase
{
    public function testBeforeSendReturnsEventUnchanged(): void
    {
        $event = new \stdClass();
        $hint = new \stdClass();

        $result = SentryBeforeSendCallback::beforeSend($event, $hint);
        $this->assertSame($event, $result);
    }
}
