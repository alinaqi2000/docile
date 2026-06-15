<?php

declare(strict_types=1);

namespace Docile\Bus\Exception;

/**
 * Thrown when a message class is dispatched re-entrantly while already being handled.
 */
final class ReentrantDispatchException extends BusException {}
