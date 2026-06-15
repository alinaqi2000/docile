<?php

declare(strict_types=1);

namespace Docile\Testing\Tests\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Docile\Testing\Http\HttpTestCase::class)]
final class HttpTestCaseTest extends TestCase
{
    public function testHttpTestCaseExtendsApplicationTestCase(): void
    {
        $this->assertTrue(is_subclass_of(\Docile\Testing\Http\HttpTestCase::class, \Docile\Testing\ApplicationTestCase::class));
    }

    public function testHttpTestCaseUsesMakesHttpRequests(): void
    {
        $this->assertContains(\Docile\Testing\Concerns\MakesHttpRequests::class, class_uses(\Docile\Testing\Http\HttpTestCase::class));
    }
}
