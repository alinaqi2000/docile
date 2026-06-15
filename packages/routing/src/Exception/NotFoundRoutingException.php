<?php

declare(strict_types=1);

namespace Docile\Routing\Exception;

/**
 * Thrown when no route matches the incoming request path.
 */
final class NotFoundRoutingException extends RoutingException {}
