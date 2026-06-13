<?php

declare(strict_types=1);

namespace Docile\Http;

use Docile\Http\Exception\EmitterException;
use Psr\Http\Message\ResponseInterface;

final class SapiEmitter
{
    public function emit(ResponseInterface $response): void
    {
        if (headers_sent()) {
            throw new EmitterException('Headers already sent');
        }

        // Send status line
        $statusLine = sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($statusLine, true, $response->getStatusCode());

        // Send headers
        foreach ($response->getHeaders() as $name => $values) {
            $first = true;
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $first);
                $first = false;
            }
        }

        // Send body
        echo $response->getBody();
    }
}