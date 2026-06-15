<?php

declare(strict_types=1);

namespace Docile\Testing;

use Docile\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

abstract class ApplicationTestCase extends TestCase
{
    private ?Application $app = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = $this->createApplication();
    }

    protected function tearDown(): void
    {
        $this->app = null;

        parent::tearDown();
    }

    abstract protected function createApplication(): Application;

    protected function app(): Application
    {
        if ($this->app === null) {
            throw new \RuntimeException('Application not initialized. Did you call parent::setUp()?');
        }

        return $this->app;
    }

    protected function container(): ContainerInterface
    {
        return $this->app()->container();
    }
}
