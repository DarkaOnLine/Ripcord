<?php

namespace Ripcord\Client;

use Ripcord\Exceptions\ConfigurationException;
use Ripcord\Exceptions\InvalidArgumentException;
use Ripcord\Exceptions\RemoteException;
use Ripcord\Ripcord;

class Client
{
    /**
     * The url of the rpc server.
     */
    private $_url = '';

    /**
     * The transport object, used to post requests.
     */
    private $_transport = null;

    /**
     * A list of output options, used with the xmlrpc_encode_request method.
     *
     * @see Ripcord_Server::setOutputOption()
     */
    private $_outputOptions = [
        'output_type' => 'xml',
        'verbosity'   => 'pretty',
        'escaping'    => ['markup'],
        'version'     => 'xmlrpc',
        'encoding'    => 'utf-8',
    ];

    /**
     * The namespace to use when calling a method.
     */
    private $_namespace = null;

    /**
     * A reference to the root client object. This is so when you use namespaced sub clients, you can always
     * find the _response and _request data in the root client.
     */
    private $_rootClient = null;

    /**
     * A flag to indicate whether or not to preemptively clone objects passed as arguments to methods, see
     * php bug #50282. Only correctly set in the rootClient.
     */
    private $_cloneObjects = false;

    /**
     * A flag to indicate if we are in a multiCall block. Start this with $client->system->multiCall()->start().
     */
    protected $_multiCall = false;

    /**
     * A list of deferred encoded calls.
     */
    protected $_multiCallArgs = [];

    /**
     * The exact response from the rpc server. For debugging purposes.
     */
    public $_response = '';

    /**
     * The exact request from the client. For debugging purposes.
     */
    public $_request = '';

    /**
     * Whether or not to throw exceptions when an xml-rpc fault is returned by the server. Default is false.
     */
    public $_throwExceptions = false;

    /**
     * Whether or not to decode the XML-RPC datetime and base64 types to unix timestamp and binary string
     * respectively.
     */
    public $_autoDecode = true;

    /**
     * The constructor for the RPC client.
     *
     * @param string $url        The url of the rpc server
     * @param array  $options    Optional. A list of outputOptions. See {@link Ripcord_Server::setOutputOption()}
     * @param null   $transport
     * @param object $rootClient Optional. Used internally when using namespaces.
     *
     * @throws ConfigurationException when the xmlrpc extension is not available.
     */
    public function __construct($url, array $options = null, $transport = null, $rootClient = null)
    {
        if (!isset($rootClient)) {
            $rootClient = $this;
            if (!function_exists('xmlrpc_encode_request')) {
                throw new ConfigurationException(
                    'PHP XMLRPC library is not installed',
                    Ripcord::XMLRPC_NOT_INSTALLED
                );
            }
            $version = explode('.', phpversion());
            if ((0 + $version[0]) == 5) {
                if ((0 + $version[1]) < 2) {
                    $this->_cloneObjects = true; // workaround for bug #50282
                }
            }
        }
        $this->_rootClient = $rootClient;
        $this->_url = $url;
        if (isset($options)) {
            if (isset($options['namespace'])) {
                $this->_namespace = $options['namespace'];
                unset($options['namespace']);
            }
            $this->_outputOptions = $options;
        }
        if (isset($transport)) {
            $this->_transport = $transport;
        }
    }

    /**
     * This method catches any native method called on the client and calls it on the rpc server instead.
     * It automatically parses the resulting xml and returns native php type results.
     *
     * @param $name
     * @param $args
     *
     * @throws RemoteException when _throwExceptions is true and the server returns an XML-RPC Fault.
     *
     * @return array|mixed when handling a multiCall and the
     *                     arguments passed do not have the correct method call information
     */
    public function __call($name, $args)
    {
        if (isset($this->_namespace)) {
            $name = $this->_namespace.'.'.$name;
        }

        if ($name === 'system.multiCall' || $name == 'system.multicall') {
            if (!$args || (is_array($args) && count($args) == 0)) {
                // multiCall is called without arguments, so return the fetch interface object
                return new MultiCall($this->_rootClient, $name);
            } elseif (is_array($args) && (count($args) == 1) &&
                is_array($args[0]) && !isset($args[0]['methodName'])) {
                // multicall is called with a simple array of calls.
                $args = $args[0];
            }
            $this->_rootClient->_multiCall = false;
            $params = [];
            $bound = [];
            foreach ($args as $key => $arg) {
                if (!is_a($arg, 'Call') &&
                    (!is_array($arg) || !isset($arg['methodName']))) {
                    throw new InvalidArgumentException(
                        'Argument '.$key.' is not a valid Ripcord call',
                        Ripcord::NOT_RIPCORD_CALL
                    );
                }
                if (is_a($arg, 'Call')) {
                    $arg->index = count($params);
                    $params[] = $arg->encode();
                } else {
                    $arg['index'] = count($params);
                    $params[] = [
                        'methodName' => $arg['methodName'],
                        'params'     => isset($arg['params']) ?
                            (array) $arg['params'] : [],
                    ];
                }
                $bound[$key] = $arg;
            }
            $args = [$params];
            $this->_rootClient->_multiCallArgs = [];
        }
        if ($this->_rootClient->_multiCall) {
            $call = new Call($name, $args);
            $this->_rootClient->_multiCallArgs[] = $call;

            return $call;
        }
        if ($this->_rootClient->_cloneObjects) { //workaround for php bug 50282
            foreach ($args as $key => $arg) {
                if (is_object($arg)) {
                    $args[$key] = clone $arg;
                }
            }
        }
        $request = xmlrpc_encode_request($name, $args, $this->_outputOptions);
        $response = $this->_transport->post($this->_url, $request);
        $result = xmlrpc_decode($response, 'utf-8');
        $this->_rootClient->_request = $request;
        $this->_rootClient->_response = $response;
        if (Ripcord::isFault($result) && $this->_throwExceptions) {
            throw new RemoteException($result['faultString'], $result['faultCode']);
        }
        if (isset($bound) && is_array($bound)) {
            foreach ($bound as $key => $callObject) {
                if (is_a($callObject, 'Call')) {
                    $returnValue = $result[$callObject->index];
                } else {
                    $returnValue = $result[$callObject['index']];
                }
                if (is_array($returnValue) && count($returnValue) == 1) {
                    // XML-RPC specification says that non-fault results must be in a single item array
                    $returnValue = current($returnValue);
                }
                if ($this->_autoDecode) {
                    $type = xmlrpc_get_type($returnValue);
                    switch ($type) {
                        case 'base64':
                            $returnValue = Ripcord::binary($returnValue);
                            break;
                        case 'datetime':
                            $returnValue = Ripcord::timestamp($returnValue);
                            break;
                    }
                }
                if (is_a($callObject, 'Call')) {
                    $callObject->bound = $returnValue;
                }
                $bound[$key] = $returnValue;
            }
            $result = $bound;
        }

        return $result;
    }

    /**
     * This method catches any reference to properties of the client and uses them as a namespace. The
     * property is automatically created as a new instance of the rpc client, with the name of the property
     * as a namespace.
     *
     * @param string $name The name of the namespace
     *
     * @return object A Ripcord Client with the given namespace set.
     */
    public function __get($name)
    {
        $result = null;
        if (!isset($this->{$name})) {
            $result = new self(
                $this->_url,
                array_merge($this->_outputOptions, [
                    'namespace' => $this->_namespace ?
                        $this->_namespace.'.'.$name : $name,
                ]),
                $this->_transport,
                $this->_rootClient
            );
            $this->{$name} = $result;
        }

        return $result;
    }

    public function getTransportOptions()
    {
        if (isset($this->_transport)) {
            return $this->_transport->getOptions();
        }
    }

    public function setTransportOptions(array $options = [])
    {
        if (isset($this->_transport)) {
            $this->_transport->setOptions($options);
        }
    }
}
