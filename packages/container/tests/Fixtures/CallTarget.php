<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class CallTarget
{
    public function handle(Logger $logger, string $suffix): string
    {
        return $logger::class . ':' . $suffix;
    }

    public static function staticHandle(Logger $logger): string
    {
        return 'static:' . $logger::class;
    }
}
