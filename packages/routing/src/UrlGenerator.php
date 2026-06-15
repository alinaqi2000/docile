<?php

declare(strict_types=1);

namespace Docile\Routing;

use Docile\Routing\Exception\RouteNotFoundException;

use function array_key_exists;
use function http_build_query;
use function ltrim;
use function preg_replace_callback;
use function rtrim;
use function sprintf;

/**
 * Generates URLs for named routes.
 */
final class UrlGenerator
{
    public function __construct(private readonly RouteCollection $collection) {}

    /**
     * Generate a URL for a named route.
     *
     * - Substitutes `{param}` placeholders with the provided values.
     * - Remaining parameters not used as path segments are appended as query string.
     * - When `$absolute` is true, prepends `$baseUrl` (trailing slash stripped).
     *
     * @param array<string, string|int|float> $params
     *
     * @throws RouteNotFoundException When no route with the given name is registered.
     */
    public function route(
        string $name,
        array $params = [],
        bool $absolute = false,
        string $baseUrl = '',
    ): string {
        $definition = $this->collection->findByName($name);

        if ($definition === null) {
            throw new RouteNotFoundException(
                sprintf('Route "%s" is not defined.', $name),
            );
        }

        /** @var array<string, string|int|float> $remaining */
        $remaining = $params;

        $path = (string) preg_replace_callback(
            '/\{(\w+)\}/',
            static function (array $m) use (&$remaining): string {
                $key = $m[1];

                if (array_key_exists($key, $remaining)) {
                    $value = (string) $remaining[$key];
                    unset($remaining[$key]);

                    return $value;
                }

                // Parameter required but not provided — leave as-is (tests can assert)
                return $m[0];
            },
            $definition->path,
        );

        if ($remaining !== []) {
            $path .= '?' . http_build_query($remaining);
        }

        if ($absolute) {
            $base = rtrim($baseUrl, '/');

            return $base . '/' . ltrim($path, '/');
        }

        return $path;
    }
}
