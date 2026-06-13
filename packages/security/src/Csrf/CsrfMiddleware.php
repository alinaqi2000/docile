<?php

declare(strict_types=1);

namespace Docile\Security\Csrf;

use Docile\Security\Exception\CsrfTokenMismatchException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CsrfMiddleware implements MiddlewareInterface
{
    private const string HEADER_NAME = 'X-CSRF-TOKEN';
    private const string FIELD_NAME = '_token';

    public function __construct(
        private readonly CsrfTokenManager $manager,
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();

        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $handler->handle($request);
        }

        $session = $request->getAttribute('session') ?? [];
        if (!is_array($session)) {
            $session = [];
        }

        /** @var array<string, mixed> $session */
        $session = $session;

        $token = $this->extractToken($request);

        if ($token === null || !$this->manager->validate($token, $session)) {
            throw new CsrfTokenMismatchException('CSRF token mismatch.');
        }

        return $handler->handle($request);
    }

    private function extractToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine(self::HEADER_NAME);
        if ($header !== '') {
            return $header;
        }

        $body = $request->getParsedBody();
        if (is_array($body) && isset($body[self::FIELD_NAME]) && is_string($body[self::FIELD_NAME])) {
            return $body[self::FIELD_NAME];
        }

        return null;
    }
}
