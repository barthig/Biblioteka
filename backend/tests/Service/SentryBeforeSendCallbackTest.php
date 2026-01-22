<?php
namespace App\Tests\Service;

use App\Service\SentryBeforeSendCallback;
use PHPUnit\Framework\TestCase;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\UserDataBag;

class SentryBeforeSendCallbackTest extends TestCase
{
    public function testFiltersSensitiveHeadersAndEnv(): void
    {
        $event = Event::createEvent();
        $event->setRequest([
            'headers' => [
                'authorization' => 'Bearer token',
                'x-api-secret' => 'secret',
                'x-custom' => 'ok',
            ],
        ]);
        $event->setExtra([
            'environment' => [
                'DATABASE_URL' => 'postgres://',
                'OTHER' => 'ok',
            ],
        ]);
        $event->setUser(new UserDataBag('user-1'));

        $result = SentryBeforeSendCallback::beforeSend($event, $this->createMock(EventHint::class));
        $this->assertSame($event, $result);

        $request = $event->getRequest();
        $this->assertSame('[FILTERED]', $request['headers']['authorization']);
        $this->assertSame('[FILTERED]', $request['headers']['x-api-secret']);
        $this->assertSame('ok', $request['headers']['x-custom']);

        $extra = $event->getExtra();
        $this->assertSame('[FILTERED]', $extra['environment']['DATABASE_URL']);
        $this->assertSame('ok', $extra['environment']['OTHER']);
        $this->assertNull($event->getUser());
    }
}
