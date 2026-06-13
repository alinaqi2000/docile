<?php

declare(strict_types=1);

namespace Docile\Validation\Tests;

use Docile\Validation\ViolationList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViolationList::class)]
final class ViolationListTest extends TestCase
{
    public function testAddAndGetViolations(): void
    {
        $violations = new ViolationList();
        
        $this->assertTrue($violations->isEmpty());
        $this->assertCount(0, $violations);
        
        $violations->add('email', 'Invalid email format.');
        $violations->add('name', 'Name is required.');
        $violations->add('email', 'Email already exists.');
        
        $this->assertFalse($violations->isEmpty());
        $this->assertCount(3, $violations);
        
        $this->assertTrue($violations->has('email'));
        $this->assertTrue($violations->has('name'));
        $this->assertFalse($violations->has('password'));
        
        $this->assertSame(['Invalid email format.', 'Email already exists.'], $violations->get('email'));
        $this->assertSame(['Name is required.'], $violations->get('name'));
        $this->assertSame([], $violations->get('password'));
        
        $all = $violations->all();
        $this->assertArrayHasKey('email', $all);
        $this->assertArrayHasKey('name', $all);
        $this->assertSame(['Invalid email format.', 'Email already exists.'], $all['email']);
        $this->assertSame(['Name is required.'], $all['name']);
    }

    public function testMerge(): void
    {
        $violations1 = new ViolationList();
        $violations1->add('email', 'Invalid email format.');
        $violations1->add('name', 'Name is required.');
        
        $violations2 = new ViolationList();
        $violations2->add('email', 'Email already exists.');
        $violations2->add('password', 'Password is too short.');
        
        $violations1->merge($violations2);
        
        $this->assertCount(4, $violations1);
        $this->assertSame(['Invalid email format.', 'Email already exists.'], $violations1->get('email'));
        $this->assertSame(['Name is required.'], $violations1->get('name'));
        $this->assertSame(['Password is too short.'], $violations1->get('password'));
    }

    public function testIteratorAggregate(): void
    {
        $violations = new ViolationList();
        $violations->add('email', 'Invalid email format.');
        $violations->add('name', 'Name is required.');
        
        $iterated = [];
        foreach ($violations as $field => $messages) {
            $iterated[$field] = $messages;
        }
        
        $this->assertSame([
            'email' => ['Invalid email format.'],
            'name' => ['Name is required.'],
        ], $iterated);
    }

    public function testEmptyList(): void
    {
        $violations = new ViolationList();
        
        $this->assertTrue($violations->isEmpty());
        $this->assertCount(0, $violations);
        $this->assertFalse($violations->has('anyfield'));
        $this->assertSame([], $violations->get('anyfield'));
        $this->assertSame([], $violations->all());
    }
}