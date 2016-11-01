<?php

namespace Ripcord\Client;

/**
 * This class provides the fetch interface for system.multiCall. It is returned
 * when calling $client->system->multiCall() with no arguments. Upon construction
 * it puts the originating client into multiCall deferred mode. The client will
 * gather the requested method calls instead of executing them immediately. It
 * will them execute all of them, in order, when calling
 * $client->system->multiCall()->fetch().
 * This class extends Ripcord_Client only so it has access to its protected _multiCall
 * property.
 */
class MultiCall extends Client
{
    /*
     * The reference to the originating client to put into multiCall mode.
     */
    private $client = null;

    /**
     * This method creates a new multiCall fetch api object.
     *
     * MultiCall constructor.
     *
     * @param string $client
     * @param string $methodName
     */
    public function __construct($client, $methodName = 'system.multiCall')
    {
        $this->client = $client;
        $this->methodName = $methodName;
    }

    /*
     * This method puts the client into multiCall mode. While in this mode all
     * method calls are collected as deferred calls (Ripcord_Client_Call).
     */

    public function start()
    {
        $this->client->_multiCall = true;
    }

    /*
     * This method finally calls the clients multiCall method with all deferred
     * method calls since multiCall mode was enabled.
     */

    public function execute()
    {
        if ($this->methodName == 'system.multiCall') {
            return $this->client->system->multiCall($this->client->_multiCallArgs);
        } else { // system.multicall
            return $this->client->system->multicall($this->client->_multiCallArgs);
        }
    }
}
