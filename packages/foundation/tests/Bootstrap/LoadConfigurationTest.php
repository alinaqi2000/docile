<?php

declare(strict_types=1);

namespace Docile\Foundation\Tests\Bootstrap;

use Docile\Config\Repository;
use Docile\Container\Container;
use Docile\Foundation\Application;
use Docile\Foundation\Bootstrap\LoadConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoadConfiguration::class)]
final class LoadConfigurationTest extends TestCase
{
    private string $configDir;

    protected function setUp(): void
    {
        $this->configDir = dirname(__DIR__) . '/Fixtures/TestConfigDir';
    }

    public function testBootstrapLoadsPhpFilesFromDirectory(): void
    {
        $container = new Container();
        $app = new Application($container);

        $bootstrapper = new LoadConfiguration($this->configDir);
        $bootstrapper->bootstrap($app);

        self::assertTrue($container->has('config'));

        /** @var Repository $config */
        $config = $container->get('config');
        self::assertInstanceOf(Repository::class, $config);
    }

    public function testBootstrapRegistersConfigRepositoryWithCorrectData(): void
    {
        $container = new Container();
        $app = new Application($container);

        $bootstrapper = new LoadConfiguration($this->configDir);
        $bootstrapper->bootstrap($app);

        /** @var Repository $config */
        $config = $container->get('config');

        // The TestConfigDir/app.php returns ['name' => 'Docile']
        // DirectoryLoader uses filename as key, so it becomes ['app' => ['name' => 'Docile']]
        self::assertSame('Docile', $config->get('app.name'));
    }

    public function testBootstrapBindsConfigAsSingleton(): void
    {
        $container = new Container();
        $app = new Application($container);

        $bootstrapper = new LoadConfiguration($this->configDir);
        $bootstrapper->bootstrap($app);

        // Resolving 'config' twice should give the same object
        $config1 = $container->get('config');
        $config2 = $container->get('config');

        self::assertSame($config1, $config2);
    }
}
