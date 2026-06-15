<?php

declare(strict_types=1);

namespace Docile\Routing\Exception;

/**
 * Thrown by UrlGenerator when a named route cannot be found.
 */
final class RouteNotFoundException extends RoutingException {}
