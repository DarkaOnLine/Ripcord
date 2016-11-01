<?php

namespace Ripcord\Client;

/**
 * This class is used with the Ripcord_Client when calling system.multiCall.
 * Instead of immediately calling the method on the rpc server, a Ripcord_Client_Call  object is created with
 * all the information needed to call the method using the multicall parameters. The call object is
 * returned immediately and is used as input parameter for the multiCall call. The result of the call can be bound
 * to a php variable. This variable will be filled with the result of the call when it is available.
 */
class Call
{
    /**
     * The method to call on the rpc server.
     */
    public $method = null;

    /**
     * The arguments to pass on to the method.
     */
    public $params = [];

    /**
     * The index in the multicall request array, if any.
     */
    public $index = null;

    /**
     * A reference to the php variable to fill with the result of the call, if any.
     */
    public $bound = null;

    /**
     * The constructor for the Ripcord_Client_Call class.
     *
     * @param string $method The name of the rpc method to call
     * @param array  $params The parameters for the rpc method.
     */
    public function __construct($method, $params)
    {
        $this->method = $method;
        $this->params = $params;
    }

    /**
     * This method allows you to bind a php variable to the result of this method call.
     * When the method call's result is available, the php variable will be filled with
     * this result.
     *
     * @param mixed $bound The variable to bind the result from this call to.
     *
     * @return object Returns this object for chaining.
     */
    public function bind(&$bound)
    {
        $this->bound = &$bound;

        return $this;
    }

    /**
     * This method returns the correct format for a multiCall argument.
     *
     * @return array An array with the methodName and params
     */
    public function encode()
    {
        return [
            'methodName' => $this->method,
            'params'     => (array) $this->params,
        ];
    }
}
