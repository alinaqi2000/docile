<?php

declare(strict_types=1);

namespace Docile\Http\Exception;

class HttpException extends \RuntimeException implements HttpExceptionInterface
{
    /** @param array<string, string> $headers */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly int $statusCode = 500,
        private readonly array $headers = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}