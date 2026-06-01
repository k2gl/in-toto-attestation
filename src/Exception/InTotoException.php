<?php

declare(strict_types=1);

namespace K2gl\InToto\Exception;

use Throwable;

/**
 * Marker interface implemented by every exception thrown by this package, so
 * all of them can be handled with a single catch block.
 */
interface InTotoException extends Throwable {}
