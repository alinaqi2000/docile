<?php

declare(strict_types=1);

namespace Docile\Testing\Tests\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Docile\Testing\Console\TestOutput::class)]
final class TestOutputTest extends TestCase
{
    public function testWriteAddsLine(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->write('Hello');

        $this->assertSame(['Hello'], $output->getLines());
    }

    public function testWriteWithNewlineAddsEmptyLine(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->write('Hello', true);

        $this->assertSame(['Hello', ''], $output->getLines());
    }

    public function testWritelnAddsLineWithEmpty(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->writeln('Hello');

        $this->assertSame(['Hello', ''], $output->getLines());
    }

    public function testInfoAddsInfoPrefix(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->info('message');

        $this->assertSame(['[INFO] message', ''], $output->getLines());
    }

    public function testWarnAddsWarnPrefix(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->warn('message');

        $this->assertSame(['[WARN] message', ''], $output->getLines());
    }

    public function testErrorAddsErrorPrefix(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->error('message');

        $this->assertSame(['[ERROR] message', ''], $output->getLines());
    }

    public function testSuccessAddsOkPrefix(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->success('message');

        $this->assertSame(['[OK] message', ''], $output->getLines());
    }

    public function testLineAddsLine(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->line('message');

        $this->assertSame(['message', ''], $output->getLines());
    }

    public function testTableWithEmptyHeadersDoesNothing(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->table([], [['row']]);

        $this->assertSame([], $output->getLines());
    }

    public function testTableWithEmptyRowsDoesNothing(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->table(['Header'], []);

        $this->assertSame([], $output->getLines());
    }

    public function testTableRendersSimpleTable(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->table(['Name', 'Email'], [['John', 'john@example.com']]);

        $lines = $output->getLines();
        $this->assertNotEmpty($lines);
    }

    public function testTableRendersMultipleRows(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->table(['Name'], [['John'], ['Jane']]);

        $lines = $output->getLines();
        $this->assertNotEmpty($lines);
    }

    public function testIsDecoratedReturnsFalse(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();

        $this->assertFalse($output->isDecorated());
    }

    public function testGetLinesReturnsAllLines(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->writeln('First');
        $output->writeln('Second');

        $lines = $output->getLines();
        $this->assertCount(4, $lines);
        $this->assertSame('First', $lines[0]);
        $this->assertSame('Second', $lines[2]);
    }

    public function testGetOutputReturnsJoinedString(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->writeln('First');
        $output->writeln('Second');

        $this->assertSame("First\n\nSecond\n", $output->getOutput());
    }

    public function testAssertContainsPassesWhenFound(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->writeln('Hello World');

        $this->assertNull($output->assertContains('Hello'));
    }

    public function testAssertContainsFailsWhenNotFound(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->writeln('Hello World');

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage("Output does not contain 'Missing'");

        $output->assertContains('Missing');
    }

    public function testAssertEmptyPassesWhenNoLines(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();

        $this->assertNull($output->assertEmpty());
    }

    public function testAssertEmptyFailsWhenHasLines(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->writeln('Hello');

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Output is not empty');

        $output->assertEmpty();
    }

    public function testMultipleWritesAccumulate(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();
        $output->write('One');
        $output->write('Two');
        $output->write('Three');

        $this->assertSame(['One', 'Two', 'Three'], $output->getLines());
    }

    public function testGetOutputHandlesEmptyBuffer(): void
    {
        $output = new \Docile\Testing\Console\TestOutput();

        $this->assertSame('', $output->getOutput());
    }
}
