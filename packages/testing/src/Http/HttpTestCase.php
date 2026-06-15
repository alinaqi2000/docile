<?php

declare(strict_types=1);

namespace Docile\Testing\Http;

use Docile\Testing\ApplicationTestCase;
use Docile\Testing\Concerns\MakesHttpRequests;

abstract class HttpTestCase extends ApplicationTestCase
{
    use MakesHttpRequests;
}
