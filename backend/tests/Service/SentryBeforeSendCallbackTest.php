<?php
namespace App\Tests\Service;

use App\Service\System\SentryBeforeSendCallback;
use PHPUnit\Framework\TestCase;

class SentryBeforeSendCallbackTest extends TestCase
{
    public function testFiltersSensitiveHeadersAndEnv(): void
    {
        if (!class_exists(\Sentry\Event::class) || !class_exists(\Sentry\EventHint::class) || !class_exists(\Sentry\UserDataBag::class)) {
            $this->markTestSkipped('Sentry SDK is not installed in this environment.');
        }

        $event = \Sentry\Event::createEvent();
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
        $event->setUser(new \Sentry\UserDataBag('user-1'));

        $result = SentryBeforeSendCallback::beforeSend($event, $this->createMock(\Sentry\EventHint::class));
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
