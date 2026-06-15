<?php

declare(strict_types=1);

namespace Docile\Bus\Exception;

/**
 * Thrown when no handler is registered for a given message.
 */
final class HandlerNotFoundException extends BusException {}
