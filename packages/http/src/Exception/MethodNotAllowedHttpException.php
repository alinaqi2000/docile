<?php

declare(strict_types=1);

namespace Docile\Http\Exception;

class MethodNotAllowedHttpException extends HttpException
{
    /** @param array<string> $allowedMethods */
    public function __construct(
        array $allowedMethods,
        string $message = 'Method Not Allowed',
        ?\Throwable $previous = null
    ) {
        $headers = ['Allow' => implode(', ', $allowedMethods)];
        parent::__construct($message, 0, $previous, 405, $headers);
    }

    /** @return array<string> */
    public function getAllowedMethods(): array
    {
        $headers = $this->getHeaders();
        $allowHeader = isset($headers['Allow']) ? (string) $headers['Allow'] : '';

        return $allowHeader !== '' ? explode(', ', $allowHeader) : [];
    }
}