<?php

namespace Ripcord\Exceptions;

use Ripcord\Exceptions\Contracts\Exception;

/**
 * This class is used whenever an argument passed to a Ripcord method is invalid for any reason.
 * Possible exceptions thrown are:
 * - ripcord::notRipcordCall (-2) Argument {index} is not a valid Ripcord call
 * - Thrown by the client when passing incorrect arguments to system.multiCall.
 * - ripcord::cannotRecurse (-3) Cannot recurse system.multiCall
 * - Thrown by the ripcord server when system.multicall is called within itself.
 * - ripcord::notDateTime (-6) Variable is not of type datetime - Thrown by the ripcord timestamp method.
 * - ripcord::notBase64 (-7) Variable is not of type base64 - Thrown by the ripcord binary method.
 * - ripcord::unknownServiceType (-8) Variable is not a classname or an object - Thrown by the ripcord server.
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}
