<?php

declare(strict_types=1);

namespace Docile\Testing\Tests\Concerns;

use Docile\Container\Container;
use Docile\Foundation\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Docile\Testing\Concerns\InteractsWithContainer::class)]
final class InteractsWithContainerTest extends TestCase
{
    use \Docile\Testing\Concerns\InteractsWithContainer;

    private Container $container;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        $this->app = new Application($this->container);
    }

    protected function tearDown(): void
    {
        $this->tearDownInteractsWithContainer();

        parent::tearDown();
    }

    protected function app(): Application
    {
        return $this->app;
    }

    public function testBindSwapsBinding(): void
    {
        $this->container->bind(\stdClass::class, fn () => new \stdClass());

        $this->bind(\stdClass::class, fn () => (object) ['swapped' => true]);

        $instance = $this->container->get(\stdClass::class);
        $this->assertTrue(isset($instance->swapped));
    }

    public function testBindSavesOriginalForRestore(): void
    {
        $original = new \stdClass();
        $original->original = true;
        $this->container->instance(\stdClass::class, $original);

        $this->bind(\stdClass::class, fn () => (object) ['swapped' => true]);

        $this->tearDownInteractsWithContainer();

        $restored = $this->container->get(\stdClass::class);
        $this->assertTrue(isset($restored->original));
    }

    public function testInstanceSwapsInstance(): void
    {
        $original = new \stdClass();
        $original->original = true;
        $this->container->instance(\stdClass::class, $original);

        $swapped = new \stdClass();
        $swapped->swapped = true;
        $this->instance(\stdClass::class, $swapped);

        $instance = $this->container->get(\stdClass::class);
        $this->assertTrue(isset($instance->swapped));
    }

    public function testInstanceSavesOriginalForRestore(): void
    {
        $original = new \stdClass();
        $original->original = true;
        $this->container->instance(\stdClass::class, $original);

        $swapped = new \stdClass();
        $swapped->swapped = true;
        $this->instance(\stdClass::class, $swapped);

        $this->tearDownInteractsWithContainer();

        $restored = $this->container->get(\stdClass::class);
        $this->assertTrue(isset($restored->original));
    }

    public function testBindWithNonExistentBindingDoesNotSaveOriginal(): void
    {
        $this->bind(\stdClass::class, fn () => (object) ['new' => true]);

        $this->tearDownInteractsWithContainer();

        $this->assertTrue($this->container->has(\stdClass::class));
    }

    public function testMultipleSwapsRestoreInCorrectOrder(): void
    {
        $first = new \stdClass();
        $first->id = 'first';
        $this->container->instance('first', $first);

        $second = new \stdClass();
        $second->id = 'second';
        $this->container->instance('second', $second);

        $this->instance('first', (object) ['id' => 'swapped-first']);
        $this->instance('second', (object) ['id' => 'swapped-second']);

        $this->tearDownInteractsWithContainer();

        $restoredFirst = $this->container->get('first');
        $restoredSecond = $this->container->get('second');

        $this->assertSame('first', $restoredFirst->id);
        $this->assertSame('second', $restoredSecond->id);
    }

    public function testBindWithStringConcrete(): void
    {
        $this->container->bind(\stdClass::class, fn () => new \stdClass());

        $this->bind(\stdClass::class, \DateTime::class);

        $instance = $this->container->get(\stdClass::class);
        $this->assertInstanceOf(\DateTime::class, $instance);
    }

    public function testBindWithClosureConcrete(): void
    {
        $this->container->bind(\stdClass::class, fn () => new \stdClass());

        $this->bind(\stdClass::class, fn () => (object) ['custom' => true]);

        $instance = $this->container->get(\stdClass::class);
        $this->assertTrue(isset($instance->custom));
    }

    public function testTearDownInteractsWithContainerClearsOriginals(): void
    {
        $original = new \stdClass();
        $original->original = true;
        $this->container->instance(\stdClass::class, $original);

        $this->bind(\stdClass::class, fn () => (object) ['swapped' => true]);

        $this->tearDownInteractsWithContainer();

        $this->tearDownInteractsWithContainer();

        $this->assertNotNull($this->container->get(\stdClass::class));
    }
}
