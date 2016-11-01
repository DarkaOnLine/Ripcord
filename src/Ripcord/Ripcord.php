<?php

namespace Ripcord;

use Ripcord\Client\Call;
use Ripcord\Client\Client;
use Ripcord\Client\Transport\Stream;
use Ripcord\Documentator\Documentor;
use Ripcord\Exceptions\InvalidArgumentException;
use Ripcord\Parsers\PhpDoc;
use Ripcord\Server\Server;

class Ripcord
{
    /**
     * Method {method} not found. - Thrown by the ripcord server when a requested method isn't found.
     */
    const METHOD_NOT_FOUND = -1;
    /**
     * Argument {index} is not a valid Ripcord call
     * - Thrown by the client when passing incorrect arguments to system.multiCall.
     */
    const NOT_RIPCORD_CALL = -2;
    /**
     * Cannot recurse system.multiCall
     * - Thrown by the ripcord server when system.multicall is called within itself.
     */
    const CANNOT_RECURSE = -3;
    /**
     * Could not access {url} - Thrown by the transport object when unable to access the given url.
     */
    const CANNOT_ACCESS_URL = -4;
    /**
     * PHP XMLRPC library is not installed
     * - Thrown by the ripcord server and client when the xmlrpc library is not installed.
     */
    const XMLRPC_NOT_INSTALLED = -5;
    /**
     * Variable is not of type datetime - Thrown by the ripcord timestamp method.
     */
    const NOT_DATE_TIME = -6;
    /**
     * Variable is not of type base64 - Thrown by the ripcord binary method.
     */
    const NOT_BASE64 = -7;
    /**
     * Variable is not a class name or an object - Thrown by the ripcord server.
     */
    const UNKNOWN_SERVICE_TYPE = -8;

    /**
     *  This method checks whether the given argument is an XML-RPC fault.
     *
     * @param mixed $fault
     *
     * @return bool
     */
    public static function isFault($fault)
    {
        if (isset($fault) && is_array($fault)) {
            return xmlrpc_is_fault($fault);
        } else {
            return false;
        }
    }

    /**
     *  This method generates an XML-RPC fault with the given code and message.
     *
     * @param int    $code
     * @param string $message
     *
     * @return array
     */
    public static function fault($code, $message)
    {
        return ['faultCode' => $code, 'faultString' => $message];
    }

    /**
     * This method returns a new Ripcord server, which by default implements XML-RPC, Simple RPC and SOAP 1.1.
     * The server will publish any methods passed through the $services argument. It can be configured through
     * the $options argument.
     *
     * @param mixed $services   Optional. Either an object or an array of objects. If the array has non-numeric keys,
     *                          the key will be used as a namespace for the methods in the object.
     * @param array $options    Optional. An array of options to set for the Ripcord server.
     * @param null  $documentor
     *
     * @return Server
     *
     * @see Ripcord_Server
     */
    public static function server($services = null, $options = null, $documentor = null)
    {
        if (!isset($documentor)) {
            $doc = ['name', 'css', 'wsdl', 'wsdl2'];
            $docOptions = [];
            foreach ($doc as $key) {
                if (isset($options[$key])) {
                    $docOptions[$key] = $options[$key];
                    unset($options[$key]);
                }
            }
            $docOptions['version'] = $options['version'];
            $documentor = self::documentor($docOptions);
        }

        return new Server($services, $options, $documentor);
    }

    /**
     * This method returns a new Ripcord client. By default this will be an XML-RPC client, but you can change this
     * through the $options argument.
     *
     * @param string $url       The url of the RPC server to connect with
     * @param array  $options   Optional. An array of options to set for the Ripcord client.
     * @param null   $transport
     *
     * @return Client
     *
     * @see Client
     */
    public static function client($url, $options = null, $transport = null)
    {
        if (!isset($transport)) {
            $transport = new Stream();
        }

        return new Client($url, $options, $transport);
    }

    /**
     * This method returns a new Ripcord documentor object.
     *
     * @param array  $options          Optional. An array of options to set for the Ripcord documentor.
     * @param object $docCommentParser Optional. An object that parses a docComment block. Must
     *                                 implement the Ripcord_Documentor_CommentParser interface.
     *
     * @return Documentor
     */
    public static function documentor($options = null, $docCommentParser = null)
    {
        if (!$docCommentParser) {
            $docCommentParser = new PhpDoc();
        }

        return new Documentor($options, $docCommentParser);
    }

    /**
     * This method returns an XML-RPC datetime object from a given unix timestamp.
     *
     * @param int $timestamp
     *
     * @return object
     */
    public static function datetime($timestamp)
    {
        $datetime = date("Ymd\TH:i:s", $timestamp);
        xmlrpc_set_type($datetime, 'datetime');

        return $datetime;
    }

    /**
     * This method returns a unix timestamp from a given XML-RPC datetime object.
     * It will throw a 'Variable is not of type datetime' Ripcord_Exception (code -6)
     * if the given argument is not of the correct type.
     *
     * @param object $datetime
     *
     * @return int
     */
    public static function timestamp($datetime)
    {
        if (xmlrpc_get_type($datetime) == 'datetime') {
            return $datetime->timestamp;
        } else {
            throw new InvalidArgumentException('Variable is not of type datetime', self::NOT_DATE_TIME);
        }
    }

    /**
     * This method returns an XML-RPC base64 object from a given binary string.
     *
     * @param string $binary
     *
     * @return object
     */
    public static function base64($binary)
    {
        xmlrpc_set_type($binary, 'base64');

        return $binary;
    }

    /**
     * This method returns a (binary) string from a given XML-RPC base64 object.
     * It will throw a 'Variable is not of type base64' Ripcord_Exception (code -7)
     * if the given argument is not of the correct type.
     *
     * @param object $base64
     *
     * @return string
     */
    public static function binary($base64)
    {
        if (xmlrpc_get_type($base64) == 'base64') {
            return $base64->scalar;
        } else {
            throw new InvalidArgumentException('Variable is not of type base64', self::NOT_BASE64);
        }
    }

    /**
     * This method returns the type of the given parameter. This can be any of the XML-RPC data types, e.g.
     * 'struct', 'int', 'string', 'base64', 'boolean', 'double', 'array' or 'datetime'.
     *
     * @param mixed $param
     *
     * @return string
     */
    public static function getType($param)
    {
        return xmlrpc_get_type($param);
    }

    /**
     * This method returns a new Ripcord client, configured to access a SOAP 1.1 server.
     *
     * @param string $url
     * @param array  $options   Optional.
     * @param null   $transport
     *
     * @return Client
     */
    public static function soapClient($url, $options = null, $transport = null)
    {
        $options['version'] = 'soap 1.1';

        return self::client($url, $options, $transport);
    }

    /**
     * This method returns a new Ripcord client, configured to access an XML-RPC server.
     *
     * @param string $url
     * @param array  $options   Optional.
     * @param null   $transport
     *
     * @return Client
     */
    public static function xmlrpcClient($url, $options = null, $transport = null)
    {
        $options['version'] = 'xmlrpc';

        return self::client($url, $options, $transport);
    }

    /**
     * This method returns a new Ripcord client, configured to access a Simple RPC server.
     *
     * @param string $url
     * @param array  $options   Optional.
     * @param null   $transport
     *
     * @return Client
     */
    public static function simpleClient($url, $options = null, $transport = null)
    {
        $options['version'] = 'simple';

        return self::client($url, $options, $transport);
    }

    /**
     * This method creates a new Ripcord_Client_Call object, which encodes the information needed for
     * a method call to an rpc server. This is mostly used for the system.multiCall method.
     *
     * @return object
     *
     * @internal param string $method The name of the method call to encode
     * @internal param mixed $args The remainder of the arguments are encoded as parameters to the call
     */
    public static function encodeCall()
    {
        $params = func_get_args();
        $method = array_shift($params);

        return new Call($method, $params);
    }

    /*
     * This method binds the first parameter to the output of a Ripcord client call. If
     * the second argument is a Ripcord_Client_Call object, it binds the parameter to it,
     * if not it simply assigns the second parameter to the first parameter.
     * This means that doing:
     * > ripcord::bind( $result, $client->someMethod() )
     * will always result in $result eventually containing the return value of $client->someMethod().
     * Whether multiCall mode has been enabled or not.
     */

    public function bind(&$bound, $call)
    {
        if (is_a($call, 'Call')) {
            $call->bound = &$bound;
        } else {
            $bound = $call;
        }
    }
}
