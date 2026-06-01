<?php

declare(strict_types=1);

namespace K2gl\InToto\Exception;

use RuntimeException;

/**
 * Thrown when a Statement or ResourceDescriptor cannot be built or parsed:
 * invalid JSON, a wrong `_type`, missing or wrongly typed fields, or values
 * that are not valid base64.
 */
final class InvalidStatementException extends RuntimeException implements InTotoException {}
