<?php

declare(strict_types=1);

namespace Docile\Console\Tests;

use Docile\Console\Output;
use PHPUnit\Framework\TestCase;

final class OutputTest extends TestCase
{
    private Output $output;
    private mixed $stream;

    protected function setUp(): void
    {
        $this->stream = fopen('php://memory', 'rw');
        $this->output = new Output($this->stream);
    }

    protected function tearDown(): void
    {
        fclose($this->stream);
    }

    public function testWrite(): void
    {
        $this->output->write('Hello');
        
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        
        $this->assertSame('Hello', $content);
    }

    public function testWriteWithNewline(): void
    {
        $this->output->write('Hello', true);
        
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        
        $this->assertSame("Hello\n", $content);
    }

    public function testWriteln(): void
    {
        $this->output->writeln('Hello');
        
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        
        $this->assertSame("Hello\n", $content);
    }

    public function testInfo(): void
    {
        $this->output->info('Test message');
        
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        
        $this->assertSame("[INFO] Test message\n", $content);
    }

    public function testWarn(): void
    {
        $this->output->warn('Test message');
        
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        
        $this->assertSame("[WARN] Test message\n", $content);
    }

    public function testError(): void
    {
        $this->output->error('Test message');
        
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        
        $this->assertSame("[ERROR] Test message\n", $content);
    }

    public function testSuccess(): void
    {
        $this->output->success('Test message');
        
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        
        $this->assertSame("[OK] Test message\n", $content);
    }

    public function testLine(): void
    {
        $this->output->line('Test message');
        
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        
        $this->assertSame("Test message\n", $content);
    }

    public function testTable(): void
    {
        $headers = ['Name', 'Age'];
        $rows = [
            ['John', '25'],
            ['Jane', '30'],
        ];
        
        $this->output->table($headers, $rows);
        
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        
        $expected = "+------+-----+\n";
        $expected .= "| Name | Age |\n";
        $expected .= "+------+-----+\n";
        $expected .= "| John | 25  |\n";
        $expected .= "| Jane | 30  |\n";
        $expected .= "+------+-----+\n";
        
        $this->assertSame($expected, $content);
    }

    public function testTableWithEmptyData(): void
    {
        $this->output->table([], []);
        
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        
        $this->assertSame('', $content);
    }

    public function testTableWithEmptyRows(): void
    {
        $this->output->table(['Header'], []);
        
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        
        $this->assertSame('', $content);
    }

    public function testIsDecoratedReturnsFalseForMemoryStream(): void
    {
        $this->assertFalse($this->output->isDecorated());
    }

    public function testDefaultConstructorUsesStdout(): void
    {
        $output = new Output();
        $this->assertInstanceOf(Output::class, $output);
    }
}