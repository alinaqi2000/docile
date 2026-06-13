<?php

declare(strict_types=1);

namespace Docile\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Base class for all container errors that are not "not found" lookups.
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface {}
