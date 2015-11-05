<?php

namespace Ripcord\Exceptions;

use Ripcord\Exceptions\Contracts\Exception;

/**
 * This class is used whenever an when a method passed to the server is invalid.
 * - ripcord::methodNotFound (-1) Method {method} not found.
 * - Thrown by the ripcord server when a requested method isn't found.
 */
class BadMethodCallException extends \BadMethodCallException implements Exception
{
}
