<?php

declare(strict_types=1);

namespace Docile\Validation\Attribute;

use Attribute;

use Docile\Validation\RuleInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Rule
{
    /** @param class-string<RuleInterface> $ruleClass */
    public function __construct(public readonly string $ruleClass) {}
}