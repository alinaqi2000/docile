<?php

declare(strict_types=1);

namespace Docile\Testing\Concerns;

use Docile\Foundation\Application;
use Docile\Testing\Http\TestRequest;
use Docile\Testing\Http\TestResponse;
use Psr\Http\Message\ResponseInterface;

trait MakesHttpRequests
{
    abstract protected function app(): Application;

    /**
     * @param array<string, string> $headers
     */
    protected function get(string $uri, array $headers = []): TestResponse
    {
        return $this->request('GET', $uri, null, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function post(string $uri, mixed $body = null, array $headers = []): TestResponse
    {
        return $this->request('POST', $uri, $body, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function put(string $uri, mixed $body = null, array $headers = []): TestResponse
    {
        return $this->request('PUT', $uri, $body, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function patch(string $uri, mixed $body = null, array $headers = []): TestResponse
    {
        return $this->request('PATCH', $uri, $body, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function delete(string $uri, mixed $body = null, array $headers = []): TestResponse
    {
        return $this->request('DELETE', $uri, $body, $headers);
    }

    protected function json(string $method, string $uri, mixed $data = null): TestResponse
    {
        $request = TestRequest::from($method, $uri);

        if ($data !== null) {
            $request = $request->withJsonBody($data);
        }

        $request = $request->asJson();

        return $this->dispatch($request);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function request(string $method, string $uri, mixed $body = null, array $headers = []): TestResponse
    {
        $request = TestRequest::from($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            if (is_array($body)) {
                $request = $request->withJsonBody($body);
            } elseif (is_string($body)) {
                $request = $request->withBody($body);
            }
        }

        return $this->dispatch($request);
    }

    protected function dispatch(TestRequest $request): TestResponse
    {
        $psrRequest = $request->build();

        $response = $this->app()->handleHttp(
            $psrRequest,
            \Docile\Http\Kernel::class,
        );

        return new TestResponse($response);
    }
}
