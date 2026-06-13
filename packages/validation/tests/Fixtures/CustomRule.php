<?php

declare(strict_types=1);

namespace Docile\Validation\Tests\Fixtures;

use Docile\Validation\RuleInterface;

final readonly class CustomRule implements RuleInterface
{
    public function __construct(private readonly string $forbiddenWord = 'forbidden') {}

    public function validate(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return 'Value must be a string.';
        }

        if (str_contains(strtolower($value), $this->forbiddenWord)) {
            return "Value cannot contain the word '{$this->forbiddenWord}'.";
        }

        return null;
    }
}