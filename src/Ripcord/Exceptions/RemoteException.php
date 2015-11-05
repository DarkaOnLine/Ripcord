<?php

namespace Ripcord\Exceptions;

use Ripcord\Exceptions\Contracts\Exception;

/**
 * This class is used for exceptions generated from xmlrpc faults returned by the server.
 * The code and message correspond
 * to the code and message from the xmlrpc fault.
 */
class RemoteException extends \Exception implements Exception
{
}
