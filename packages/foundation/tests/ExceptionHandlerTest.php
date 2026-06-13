<?php

declare(strict_types=1);

namespace Docile\Foundation\Tests;

use Docile\Foundation\ExceptionHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ExceptionHandler::class)]
final class ExceptionHandlerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // handleHttp()
    // -------------------------------------------------------------------------

    public function testHandleHttpReturnsProblemJsonResponse(): void
    {
        $handler = new ExceptionHandler();
        $e = new RuntimeException('Something broke');

        $response = $handler->handleHttp($e);

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('application/problem+json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertSame(500, $body['status']);
        self::assertSame('Something broke', $body['detail']);
        self::assertSame('about:blank', $body['type']);
        self::assertSame('Internal Server Error', $body['title']);
    }

    public function testHandleHttpUsesExceptionCodeAsHttpStatusWhenValid(): void
    {
        $handler = new ExceptionHandler();
        $e = new RuntimeException('Not found', 404);

        $response = $handler->handleHttp($e);

        self::assertSame(404, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertSame(404, $body['status']);
        self::assertSame('Not Found', $body['title']);
    }

    public function testHandleHttpFallsBackTo500ForInvalidCode(): void
    {
        $handler = new ExceptionHandler();
        $e = new RuntimeException('oops', 0);

        $response = $handler->handleHttp($e);

        self::assertSame(500, $response->getStatusCode());
    }

    public function testHandleHttpFallsBackTo500ForCodeBelow400(): void
    {
        $handler = new ExceptionHandler();
        $e = new RuntimeException('oops', 200);

        $response = $handler->handleHttp($e);

        self::assertSame(500, $response->getStatusCode());
    }

    public function testHandleHttpExcludesTraceInNonDebugMode(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $e = new RuntimeException('fail');

        $response = $handler->handleHttp($e);

        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertArrayNotHasKey('trace', $body);
    }

    public function testHandleHttpIncludesTraceInDebugMode(): void
    {
        $handler = new ExceptionHandler(debug: true);
        $e = new RuntimeException('fail');

        $response = $handler->handleHttp($e);

        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertArrayHasKey('trace', $body);
        self::assertIsString($body['trace']);
    }

    public function testHandleHttpStatusTitlesForKnown4xxCodes(): void
    {
        $handler = new ExceptionHandler();

        $cases = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
        ];

        foreach ($cases as $code => $expectedTitle) {
            $e = new RuntimeException('x', $code);
            $response = $handler->handleHttp($e);

            $body = json_decode((string) $response->getBody(), true);
            self::assertIsArray($body);
            self::assertSame($expectedTitle, $body['title'], "Title mismatch for HTTP $code");
        }
    }

    public function testHandleHttpStatusTitlesForKnown5xxCodes(): void
    {
        $handler = new ExceptionHandler();

        $cases = [
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        foreach ($cases as $code => $expectedTitle) {
            $e = new RuntimeException('x', $code);
            $response = $handler->handleHttp($e);

            $body = json_decode((string) $response->getBody(), true);
            self::assertIsArray($body);
            self::assertSame($expectedTitle, $body['title'], "Title mismatch for HTTP $code");
        }
    }

    public function testHandleHttpUnknownCodeUsesErrorTitle(): void
    {
        $handler = new ExceptionHandler();
        $e = new RuntimeException('x', 418);

        $response = $handler->handleHttp($e);

        self::assertSame(418, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        // 418 is not in the match, so title => 'Error'
        self::assertSame('Error', $body['title']);
    }

    // -------------------------------------------------------------------------
    // handleConsole()
    // -------------------------------------------------------------------------

    public function testHandleConsoleWritesErrorMessageToStream(): void
    {
        $handler = new ExceptionHandler();
        $e = new RuntimeException('Something console-bad happened');

        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);

        $exitCode = $handler->handleConsole($e, $stream);

        rewind($stream);
        $output = stream_get_contents($stream);
        fclose($stream);

        self::assertSame(1, $exitCode);
        self::assertSame("Error: Something console-bad happened\n", $output);
    }

    public function testHandleConsoleAlwaysReturnsOne(): void
    {
        $handler = new ExceptionHandler();
        $e = new RuntimeException('any');

        $stream = fopen('php://memory', 'w');
        self::assertIsResource($stream);

        $exitCode = $handler->handleConsole($e, $stream);
        fclose($stream);

        self::assertSame(1, $exitCode);
    }
}
