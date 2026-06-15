<?php

declare(strict_types=1);

namespace Docile\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RedirectResponseTest extends TestCase
{
    public function testMakeWithDefaults(): void
    {
        $location = '/new-url';
        $response = \Docile\Http\RedirectResponse::make($location);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame($location, $response->getHeaderLine('Location'));
        $this->assertSame('', (string) $response->getBody());
    }

    public function testMakeWithCustomStatus(): void
    {
        $location = '/moved-permanently';
        $response = \Docile\Http\RedirectResponse::make($location, 301);

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame($location, $response->getHeaderLine('Location'));
    }

    public function testMakeWithHeaders(): void
    {
        $location = '/target';
        $headers = ['X-Custom' => ['value']];
        $response = \Docile\Http\RedirectResponse::make($location, 302, $headers);

        $this->assertSame($location, $response->getHeaderLine('Location'));
        $this->assertSame(['value'], $response->getHeader('X-Custom'));
    }

    public function testMakeMergesLocationHeader(): void
    {
        $location = '/correct-location';
        $headers = ['Location' => ['/wrong-location']];
        $response = \Docile\Http\RedirectResponse::make($location, 302, $headers);

        $this->assertSame($location, $response->getHeaderLine('Location'));
    }

    public function testMakeWithAbsoluteUrl(): void
    {
        $location = 'https://example.com/target';
        $response = \Docile\Http\RedirectResponse::make($location);

        $this->assertSame($location, $response->getHeaderLine('Location'));
    }

    public function testMakeWithDifferentStatusCodes(): void
    {
        $location = '/test';
        
        $response301 = \Docile\Http\RedirectResponse::make($location, 301);
        $this->assertSame(301, $response301->getStatusCode());
        
        $response302 = \Docile\Http\RedirectResponse::make($location, 302);
        $this->assertSame(302, $response302->getStatusCode());
        
        $response303 = \Docile\Http\RedirectResponse::make($location, 303);
        $this->assertSame(303, $response303->getStatusCode());
        
        $response307 = \Docile\Http\RedirectResponse::make($location, 307);
        $this->assertSame(307, $response307->getStatusCode());
        
        $response308 = \Docile\Http\RedirectResponse::make($location, 308);
        $this->assertSame(308, $response308->getStatusCode());
    }
}