<?php

declare(strict_types=1);

namespace Docile\Routing\Exception;

use Throwable;

/**
 * Thrown when a route path matches but the HTTP method is not allowed.
 * Carries the list of methods that are permitted for the matched path.
 */
final class MethodNotAllowedRoutingException extends RoutingException
{
    /** @param array<string> $allowedMethods */
    public function __construct(
        private readonly array $allowedMethods,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message !== '' ? $message : sprintf(
            'Method not allowed. Allowed methods: %s',
            implode(', ', $allowedMethods),
        ), $code, $previous);
    }

    /** @return array<string> */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
