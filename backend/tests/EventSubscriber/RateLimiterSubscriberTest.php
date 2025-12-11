<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\RateLimiterSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\LimiterInterface;

class RateLimiterSubscriberTest extends TestCase
{
    public function testSkipsNonApiRoutes(): void
    {
        $anonymousLimiter = $this->createMock(RateLimiterFactory::class);
        $authenticatedLimiter = $this->createMock(RateLimiterFactory::class);
        
        $subscriber = new RateLimiterSubscriber($anonymousLimiter, $authenticatedLimiter);
        
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/health']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        // Should not create any limiters for non-API routes
        $anonymousLimiter->expects($this->never())->method('create');
        $authenticatedLimiter->expects($this->never())->method('create');
        
        $subscriber->onKernelRequest($event);
        
        $this->assertNull($event->getResponse());
    }

    public function testSkipsHealthCheckEndpoint(): void
    {
        $anonymousLimiter = $this->createMock(RateLimiterFactory::class);
        $authenticatedLimiter = $this->createMock(RateLimiterFactory::class);
        
        $subscriber = new RateLimiterSubscriber($anonymousLimiter, $authenticatedLimiter);
        
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/health']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $anonymousLimiter->expects($this->never())->method('create');
        $authenticatedLimiter->expects($this->never())->method('create');
        
        $subscriber->onKernelRequest($event);
        
        $this->assertNull($event->getResponse());
    }

    public function testAllowsRequestWithinLimit(): void
    {
        $limiter = $this->createMock(LimiterInterface::class);
        $rateLimit = $this->createMock(RateLimit::class);
        
        $rateLimit->method('isAccepted')->willReturn(true);
        $rateLimit->method('getLimit')->willReturn(30);
        $rateLimit->method('getRemainingTokens')->willReturn(29);
        
        $limiter->method('consume')->willReturn($rateLimit);
        
        $anonymousLimiter = $this->createMock(RateLimiterFactory::class);
        $anonymousLimiter->method('create')->willReturn($limiter);
        
        $authenticatedLimiter = $this->createMock(RateLimiterFactory::class);
        
        $subscriber = new RateLimiterSubscriber($anonymousLimiter, $authenticatedLimiter);
        
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/books']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $subscriber->onKernelRequest($event);
        
        $this->assertNull($event->getResponse());
        $this->assertIsArray($request->attributes->get('rate_limit'));
    }

    public function testBlocksRequestWhenLimitExceeded(): void
    {
        $limiter = $this->createMock(LimiterInterface::class);
        $rateLimit = $this->createMock(RateLimit::class);
        
        $rateLimit->method('isAccepted')->willReturn(false);
        $rateLimit->method('getLimit')->willReturn(30);
        $rateLimit->method('getRemainingTokens')->willReturn(0);
        $rateLimit->method('getRetryAfter')->willReturn(new \DateTimeImmutable('+60 seconds'));
        
        $limiter->method('consume')->willReturn($rateLimit);
        
        $anonymousLimiter = $this->createMock(RateLimiterFactory::class);
        $anonymousLimiter->method('create')->willReturn($limiter);
        
        $authenticatedLimiter = $this->createMock(RateLimiterFactory::class);
        
        $subscriber = new RateLimiterSubscriber($anonymousLimiter, $authenticatedLimiter);
        
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/books']);
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
