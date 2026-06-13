<?php

declare(strict_types=1);

namespace Docile\Validation\Exception;

use Docile\Validation\ViolationList;

final class ValidationException extends \RuntimeException
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