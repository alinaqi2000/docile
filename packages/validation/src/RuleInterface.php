<?php

declare(strict_types=1);

namespace Docile\Validation;

interface RuleInterface
{
    /** Return null if valid, or an error message string if invalid */
    public function validate(mixed $value, string $field): ?string;
}