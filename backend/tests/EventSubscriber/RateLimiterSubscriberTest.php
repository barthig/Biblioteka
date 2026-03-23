<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\RateLimiterSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class RateLimiterSubscriberTest extends TestCase
{
    private function createFactory(int $limit = 30): RateLimiterFactory
    {
        return new RateLimiterFactory([
            'id' => 'test',
            'policy' => 'fixed_window',
            'limit' => $limit,
            'interval' => '1 minute',
        ], new InMemoryStorage());
    }

    public function testSkipsNonApiRoutes(): void
    {
        $subscriber = new RateLimiterSubscriber($this->createFactory(), $this->createFactory());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/health', 'REMOTE_ADDR' => '127.0.0.1']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
        $this->assertNull($request->attributes->get('rate_limit'));
    }

    public function testSkipsHealthCheckEndpoint(): void
    {
        $subscriber = new RateLimiterSubscriber($this->createFactory(), $this->createFactory());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/health', 'REMOTE_ADDR' => '127.0.0.1']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
        $this->assertNull($request->attributes->get('rate_limit'));
    }

    public function testAllowsRequestWithinLimit(): void
    {
        $subscriber = new RateLimiterSubscriber($this->createFactory(30), $this->createFactory(30));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/books', 'REMOTE_ADDR' => '127.0.0.1']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
        $this->assertIsArray($request->attributes->get('rate_limit'));
    }

    public function testBlocksRequestWhenLimitExceeded(): void
    {
        $anonymousLimiter = $this->createFactory(1);
        $authenticatedLimiter = $this->createFactory(1);
        $anonymousLimiter->create('127.0.0.1')->consume(1);

        $subscriber = new RateLimiterSubscriber($anonymousLimiter, $authenticatedLimiter);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/books', 'REMOTE_ADDR' => '127.0.0.1']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(429, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Too Many Requests', $content['error']);
        $this->assertArrayHasKey('retry_after', $content);
    }
}
