<?php

declare(strict_types=1);

namespace Docile\Testing\Tests\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Docile\Testing\Console\ConsoleTestCase::class)]
final class ConsoleTestCaseTest extends TestCase
{
    public function testConsoleTestCaseExtendsApplicationTestCase(): void
    {
        $this->assertTrue(is_subclass_of(\Docile\Testing\Console\ConsoleTestCase::class, \Docile\Testing\ApplicationTestCase::class));
    }
}
