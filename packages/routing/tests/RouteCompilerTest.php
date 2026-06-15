<?php

declare(strict_types=1);

namespace Docile\Routing\Tests;

use Docile\Routing\CompiledRoute;
use Docile\Routing\RouteCompiler;
use Docile\Routing\RouteDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RouteCompiler::class)]
#[CoversClass(CompiledRoute::class)]
#[CoversClass(RouteDefinition::class)]
final class RouteCompilerTest extends TestCase
{
    private RouteCompiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new RouteCompiler();
    }

    // ----------------------------------------------------------------------- static routes

    public function testStaticRouteProducesLiteralRegex(): void
    {
        $compiled = $this->compiler->compile($this->makeDefinition('/users'));

        self::assertSame([], $compiled->paramNames);
        self::assertSame(1, preg_match($compiled->regex, '/users'));
        self::assertSame(0, preg_match($compiled->regex, '/users/'));
        self::assertSame(0, preg_match($compiled->regex, '/other'));
    }

    public function testStaticRootRoute(): void
    {
        $compiled = $this->compiler->compile($this->makeDefinition('/'));

        self::assertSame(1, preg_match($compiled->regex, '/'));
        self::assertSame(0, preg_match($compiled->regex, '/other'));
    }

    public function testStaticRouteWithSpecialRegexChars(): void
    {
        $compiled = $this->compiler->compile($this->makeDefinition('/users.list'));

        self::assertSame(1, preg_match($compiled->regex, '/users.list'));
        // The dot in the path must be escaped — 'users_list' should NOT match
        self::assertSame(0, preg_match($compiled->regex, '/users_list'));
    }

    // ----------------------------------------------------------------------- dynamic routes

    public function testSingleParamProducesNamedCapture(): void
    {
        $compiled = $this->compiler->compile($this->makeDefinition('/users/{id}'));

        self::assertSame(['id'], $compiled->paramNames);
        self::assertSame(1, preg_match($compiled->regex, '/users/42', $m));
        self::assertSame('42', $m['id']);
    }

    public function testMultipleParamsProduceOrderedCaptures(): void
    {
        $compiled = $this->compiler->compile($this->makeDefinition('/posts/{year}/{slug}'));

        self::assertSame(['year', 'slug'], $compiled->paramNames);
        self::assertSame(1, preg_match($compiled->regex, '/posts/2024/hello-world', $m));
        self::assertSame('2024', $m['year']);
        self::assertSame('hello-world', $m['slug']);
    }

    public function testDefaultParamPatternDoesNotMatchSlash(): void
    {
        $compiled = $this->compiler->compile($this->makeDefinition('/users/{id}/profile'));

        // Should not match when the param segment contains a slash
        self::assertSame(0, preg_match($compiled->regex, '/users/1/2/profile'));
        self::assertSame(1, preg_match($compiled->regex, '/users/1/profile'));
    }

    public function testWhereConstraintOverridesDefaultPattern(): void
    {
        $definition = new RouteDefinition(
            methods: ['GET'],
            path: '/users/{id}',
            handler: static fn (): string => 'ok',
            wheres: ['id' => '[0-9]+'],
        );

        $compiled = $this->compiler->compile($definition);

        self::assertSame(1, preg_match($compiled->regex, '/users/123'));
        self::assertSame(0, preg_match($compiled->regex, '/users/abc'));
    }

    public function testWhereConstraintAppliedToSpecificParam(): void
    {
        $definition = new RouteDefinition(
            methods: ['GET'],
            path: '/posts/{year}/{slug}',
            handler: static fn (): string => 'ok',
            wheres: ['year' => '[0-9]{4}'],
        );

        $compiled = $this->compiler->compile($definition);

        // Valid year + slug
        self::assertSame(1, preg_match($compiled->regex, '/posts/2024/hello'));
        // Invalid year (letters)
        self::assertSame(0, preg_match($compiled->regex, '/posts/abc/hello'));
    }

    public function testCompiledRouteHoldsDefinitionReference(): void
    {
        $definition = $this->makeDefinition('/test');
        $compiled = $this->compiler->compile($definition);

        self::assertSame($definition, $compiled->definition);
    }

    // ------------------------------------------------------------------ helpers

    private function makeDefinition(string $path): RouteDefinition
    {
        return new RouteDefinition(
            methods: ['GET'],
            path: $path,
            handler: static fn (): string => 'ok',
        );
    }
}
