<?php

declare(strict_types=1);

namespace Docile\Validation\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class StringType
{
    public function __construct() {}
}