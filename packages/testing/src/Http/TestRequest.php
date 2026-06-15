<?php

declare(strict_types=1);

namespace Docile\Testing\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

final class TestRequest
{
    private string $method;

    private string $uri;

    /** @var array<string, string> */
    private array $headers = [];

    private string $body = '';

    /** @var array<string, string> */
    private array $queryParams = [];

    /** @var array<string, string> */
    private array $cookies = [];

    private bool $asJson = false;

    private function __construct(string $method, string $uri)
    {
        $this->method = $method;
        $this->uri = $uri;
    }

    public static function from(string $method, string $uri): self
    {
        return new self($method, $uri);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function withBody(string $body): self
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    public function withJsonBody(mixed $data): self
    {
        $clone = clone $this;
        $encoded = json_encode($data);

        if ($encoded === false) {
            throw new \RuntimeException('Failed to encode JSON data');
        }

        $clone->body = $encoded;
        $clone->asJson = true;

        return $clone;
    }

    /**
     * @param array<string, string> $params
     */
    public function withFormParams(array $params): self
    {
        $clone = clone $this;
        $clone->body = http_build_query($params);

        return $clone;
    }

    /**
     * @param array<string, string> $params
     */
    public function withQueryParams(array $params): self
    {
        $clone = clone $this;
        $clone->queryParams = $params;

        return $clone;
    }

    public function withCookie(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->cookies[$name] = $value;

        return $clone;
    }

    public function asJson(): self
    {
        $clone = clone $this;
        $clone->asJson = true;

        return $clone;
    }

    public function build(): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
        );

        $server = [
            'REQUEST_METHOD' => $this->method,
            'REQUEST_URI' => $this->uri,
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
        ];

        $request = $creator->fromArrays($server, [], [], $this->queryParams, [], [], $this->body);

        foreach ($this->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($this->asJson && !isset($this->headers['Content-Type'])) {
            $request = $request->withHeader('Content-Type', 'application/json');
        }

        if ($this->cookies !== []) {
            $request = $request->withCookieParams($this->cookies);
        }

        return $request;
    }
}
