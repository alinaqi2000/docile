<?php

declare(strict_types=1);

namespace Docile\Security\Auth\Token;

use Docile\Security\Exception\InvalidTokenException;

final class TokenGuard
{
    public function __construct(
        private readonly JwtCodec $codec,
    ) {}

    /** Authenticate a bearer token and return claims. */
    /** @return array<string, mixed> */
    public function authenticate(string $bearerToken, string $secret): array
    {
        if (!str_starts_with($bearerToken, 'Bearer ')) {
            throw new InvalidTokenException('Invalid bearer token format.');
        }

        $token = substr($bearerToken, 7);

        return $this->codec->decode($token, $secret);
    }
}
