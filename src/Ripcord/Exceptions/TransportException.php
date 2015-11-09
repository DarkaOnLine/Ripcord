<?php

namespace Ripcord\Exceptions;

use Ripcord\Exceptions\Contracts\Exception;

/**
 * This class is used whenever something goes wrong in sending / receiving data. Possible exceptions thrown are:
 * - ripcord::cannotAccessURL (-4) Could not access {url} - Thrown by the transport object.
 */
class TransportException extends \RuntimeException implements Exception
{
}
