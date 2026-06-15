<?php

declare(strict_types=1);

namespace Docile\Bus\Tests;

use Docile\Bus\MapLocator;
use Docile\Bus\MessageBus;
use Docile\Bus\QueryBus;
use Docile\Bus\Tests\Fixtures\GetUserHandler;
use Docile\Bus\Tests\Fixtures\GetUserQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QueryBus::class)]
final class QueryBusTest extends TestCase
{
    public function testAskDelegatesToUnderlyingBus(): void
    {
        $handler = new GetUserHandler();
        $locator = new MapLocator([
            GetUserQuery::class => static function (object $msg) use ($handler): mixed {
                \assert($msg instanceof GetUserQuery);

                return $handler($msg);
            },
        ]);

        $queryBus = new QueryBus(new MessageBus($locator));
        $result = $queryBus->ask(new GetUserQuery(42));

        self::assertSame(['id' => 42, 'name' => 'User 42'], $result);
    }
}
