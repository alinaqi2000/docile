<?php

declare(strict_types=1);

namespace Docile\Foundation;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function fwrite;
use function json_encode;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * Central error handler for both HTTP and console contexts.
 */
final class ExceptionHandler
{
    public function __construct(private readonly bool $debug = false) {}

    /**
     * Converts a Throwable to a PSR-7 ResponseInterface using problem+json (RFC 7807) format.
     */
    public function handleHttp(Throwable $e): ResponseInterface
    {
        $status = $this->resolveHttpStatus($e);

        /** @var array<string, mixed> $body */
        $body = [
            'type'   => 'about:blank',
            'title'  => $this->statusTitle($status),
            'status' => $status,
            'detail' => $e->getMessage(),
        ];

        if ($this->debug) {
            $body['trace'] = $e->getTraceAsString();
        }

        $json = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return new Response(
            $status,
            ['Content-Type' => 'application/problem+json'],
            $json,
        );
    }

    /**
     * Writes "Error: {message}" to the given output stream and returns exit code 1.
     *
     * @param resource|mixed $output
     */
    public function handleConsole(Throwable $e, mixed $output): int
    {
        /** @var resource $output */
        fwrite($output, sprintf("Error: %s\n", $e->getMessage()));

        return 1;
    }

    private function resolveHttpStatus(Throwable $e): int
    {
        $code = $e->getCode();

        if (is_int($code) && $code >= 400 && $code < 600) {
            return $code;
        }

        return 500;
    }

    private function statusTitle(int $status): string
    {
        return match ($status) {
            400     => 'Bad Request',
            401     => 'Unauthorized',
            403     => 'Forbidden',
            404     => 'Not Found',
            405     => 'Method Not Allowed',
            408     => 'Request Timeout',
            409     => 'Conflict',
            410     => 'Gone',
            422     => 'Unprocessable Entity',
            429     => 'Too Many Requests',
            500     => 'Internal Server Error',
            501     => 'Not Implemented',
            502     => 'Bad Gateway',
            503     => 'Service Unavailable',
            504     => 'Gateway Timeout',
            default => 'Error',
        };
    }
}
