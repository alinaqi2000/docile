<?php

declare(strict_types=1);

namespace Docile\Testing\Tests;

use Docile\Container\Container;
use Docile\Foundation\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Docile\Testing\ApplicationTestCase::class)]
final class ApplicationTestCaseTest extends TestCase
{
    private ?\Docile\Testing\ApplicationTestCase $testCase = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testCase = new class ('', [], '') extends \Docile\Testing\ApplicationTestCase {
            private Container $container;
            private Application $app;

            protected function createApplication(): Application
            {
                $this->container = new Container();
                $this->app = new Application($this->container);
                return $this->app;
            }

            public function getContainerForTest(): Container
            {
                return $this->container;
            }
        };

        $this->testCase->setUp();
    }

    protected function tearDown(): void
    {
        if ($this->testCase !== null) {
            $this->testCase->tearDown();
        }

        parent::tearDown();
    }

    public function testAppReturnsApplication(): void
    {
        $testCase = new class ('', [], '') extends \Docile\Testing\ApplicationTestCase {
            private Container $container;
            private Application $app;

            protected function createApplication(): Application
            {
                $this->container = new Container();
                $this->app = new Application($this->container);
                return $this->app;
            }

            public function getAppForTest(): Application
            {
                return $this->app();
            }

            public function getContainerForTest(): Container
            {
                return $this->container;
            }
        };

        $testCase->setUp();
        $app = $testCase->getAppForTest();

        $this->assertInstanceOf(Application::class, $app);
    }

    public function testContainerReturnsContainer(): void
    {
        $testCase = new class ('', [], '') extends \Docile\Testing\ApplicationTestCase {
            private Container $container;
            private Application $app;

            protected function createApplication(): Application
            {
                $this->container = new Container();
                $this->app = new Application($this->container);
                return $this->app;
            }

            public function getContainerForTest(): Container
            {
                return $this->container;
            }
        };

        $testCase->setUp();
        $container = $testCase->getContainerForTest();

        $this->assertInstanceOf(Container::class, $container);
    }

    public function testContainerIsSameAsAppContainer(): void
    {
        $testCase = new class ('', [], '') extends \Docile\Testing\ApplicationTestCase {
            private Container $container;
            private Application $app;

            protected function createApplication(): Application
            {
                $this->container = new Container();
                $this->app = new Application($this->container);
                return $this->app;
            }

            public function getAppForTest(): Application
            {
                return $this->app();
            }

            public function getContainerForTest(): Container
            {
                return $this->container;
            }
        };

        $testCase->setUp();
        $app = $testCase->getAppForTest();
        $container = $testCase->getContainerForTest();

        $this->assertSame($app->container(), $container);
    }

    public function testAppThrowsWhenNotInitialized(): void
    {
        $testCase = new class ('', [], '') extends \Docile\Testing\ApplicationTestCase {
            protected function createApplication(): Application
            {
                throw new \RuntimeException('Should not be called');
            }

            public function getAppForTest(): Application
            {
                return $this->app();
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Application not initialized');

        $testCase->getAppForTest();
    }

    public function testSetUpCallsCreateApplication(): void
    {
        $testCase = new class ('', [], '') extends \Docile\Testing\ApplicationTestCase {
            public bool $createApplicationCalled = false;

            protected function createApplication(): Application
            {
                $this->createApplicationCalled = true;
                return new Application(new Container());
            }
        };

        $testCase->setUp();

        $this->assertTrue($testCase->createApplicationCalled);
    }

    public function testTearDownClearsApp(): void
    {
        $testCase = new class ('', [], '') extends \Docile\Testing\ApplicationTestCase {
            private Container $container;
            private Application $app;

            protected function createApplication(): Application
            {
                $this->container = new Container();
                $this->app = new Application($this->container);
                return $this->app;
            }

            public function getAppForTest(): Application
            {
                return $this->app();
            }
        };

        $testCase->setUp();
        $testCase->tearDown();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Application not initialized');

        $testCase->getAppForTest();
    }
}
