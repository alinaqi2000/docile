<?php

declare(strict_types=1);

namespace Docile\Security\Auth;

use Docile\Security\Auth\Token\JwtCodec;
use Docile\Security\Auth\Token\TokenGuard;
use Docile\Security\Exception\AuthenticationException;
use Docile\Security\Exception\InvalidTokenException;
use Docile\Security\Password\HasherInterface;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_array;
use function is_int;
use function is_string;

final class AuthMiddleware implements MiddlewareInterface
{
    private const string AUTH_ATTRIBUTE = 'user';
    private const string SESSION_ATTRIBUTE = 'session';

    public function __construct(
        private readonly UserProviderInterface $provider,
        private readonly HasherInterface $hasher,
        private readonly string $jwtSecret,
        private readonly ClockInterface $clock,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->authenticate($request);

        if ($user !== null) {
            $request = $request->withAttribute(self::AUTH_ATTRIBUTE, $user);
        }

        return $handler->handle($request);
    }

    private function authenticate(ServerRequestInterface $request): ?UserInterface
    {
        $session = $request->getAttribute(self::SESSION_ATTRIBUTE);
        if (is_array($session)) {
            /** @var array<string, mixed> $session */
            $user = $this->authenticateViaSession($session);
            if ($user !== null) {
                return $user;
            }
        }

        $authorization = $request->getHeaderLine('Authorization');
        if ($authorization !== '') {
            return $this->authenticateViaBearerToken($authorization);
        }

        $apiKey = $request->getHeaderLine('X-API-Key');
        if ($apiKey !== '') {
            return $this->authenticateViaApiKey($apiKey);
        }

        return null;
    }

    /** @param array<string, mixed> $session */
    private function authenticateViaSession(array $session): ?UserInterface
    {
        $guard = new SessionGuard($this->provider, $this->hasher);

        return $guard->user($session);
    }

    private function authenticateViaBearerToken(string $authorization): ?UserInterface
    {
        if (!str_starts_with($authorization, 'Bearer ')) {
            return null;
        }

        $codec = new JwtCodec($this->clock);
        $guard = new TokenGuard($codec);

        try {
            $claims = $guard->authenticate($authorization, $this->jwtSecret);
        } catch (InvalidTokenException) {
            return null;
        }

        $userId = $claims['sub'] ?? null;
        if ($userId === null) {
            return null;
        }

        if (!is_int($userId) && !is_string($userId)) {
            return null;
        }

        $user = $this->provider->findById($userId);

        if ($user === null) {
            return null;
        }

        return $user;
    }

    private function authenticateViaApiKey(string $apiKey): ?UserInterface
    {
        $guard = new ApiKeyGuard($this->provider);

        try {
            return $guard->authenticateByKey($apiKey);
        } catch (AuthenticationException) {
            return null;
        }
    }
}
