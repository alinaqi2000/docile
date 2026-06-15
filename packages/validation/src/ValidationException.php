<?php

declare(strict_types=1);

namespace Docile\Validation;

use Docile\Validation\ViolationList;

/** @deprecated Use Docile\Validation\Exception\ValidationException instead */
class ValidationException extends \RuntimeException
{
    public function __construct(private ViolationList $violations)
    {
        parent::__construct('Validation failed.');
    }

    public function violations(): ViolationList
    {
        return $this->violations;
    }
}