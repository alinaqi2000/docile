<?php

declare(strict_types=1);

namespace Docile\Testing\Http;

use Docile\Testing\Exception\AssertionFailedException;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

final class TestResponse
{
    public function __construct(
        private readonly ResponseInterface $response,
    ) {}

    public function assertStatus(int $expected): void
    {
        $actual = $this->response->getStatusCode();

        if ($actual !== $expected) {
            Assert::fail("Expected status code {$expected}, got {$actual}");
        }
    }

    public function assertOk(): void
    {
        $this->assertStatus(200);
    }

    public function assertCreated(): void
    {
        $this->assertStatus(201);
    }

    public function assertNotFound(): void
    {
        $this->assertStatus(404);
    }

    public function assertUnprocessable(): void
    {
        $this->assertStatus(422);
    }

    public function assertHeader(string $name, string $expected): void
    {
        $actual = $this->response->getHeaderLine($name);

        if ($actual !== $expected) {
            Assert::fail("Expected header '{$name}' to be '{$expected}', got '{$actual}'");
        }
    }

    /**
     * @param array<string, mixed> $expected
     */
    public function assertJson(array $expected): void
    {
        $actual = $this->getJson();

        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $actual)) {
                Assert::fail("JSON key '{$key}' not found in response");
            }

            if ($actual[$key] !== $value) {
                Assert::fail("JSON key '{$key}' expected to be " . var_export($value, true) . ', got ' . var_export($actual[$key], true));
            }
        }
    }

    public function assertJsonPath(string $path, mixed $expected): void
    {
        $actual = $this->getJson();
        $value = $this->getJsonPathValue($actual, $path);

        if ($value !== $expected) {
            Assert::fail("JSON path '{$path}' expected to be " . var_export($expected, true) . ', got ' . var_export($value, true));
        }
    }

    public function assertBodyContains(string $needle): void
    {
        $body = $this->getBody();

        if (str_contains($body, $needle) === false) {
            Assert::fail("Response body does not contain '{$needle}'");
        }
    }

    /**
     * @return array<string, mixed>
     * @phpstan-ignore return.type
     */
    public function getJson(): array
    {
        $body = $this->getBody();
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Response body is not valid JSON');
        }

        return $decoded;
    }

    public function getBody(): string
    {
        return (string) $this->response->getBody();
    }

    public function getStatus(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getJsonPathValue(array $data, string $path): mixed
    {
        $segments = explode('.', $path);
        $current = $data;

        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                $segment = (int) $segment;
            }

            if (!is_array($current) || !array_key_exists($segment, $current)) {
                Assert::fail("JSON path '{$path}' does not exist");
            }

            $current = $current[$segment];
        }

        return $current;
    }
}
