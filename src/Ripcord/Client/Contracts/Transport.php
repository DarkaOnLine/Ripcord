<?php

namespace Ripcord\Client\Contracts;

/**
 * This interface describes the minimum interface needed for the transport object used by the
 * Ripcord_Client.
 */
interface Transport
{
    /**
     * This method must post the request to the given url and return the results.
     *
     * @param string $url     The url to post to.
     * @param string $request The request to post.
     *
     * @return string The server response
     */
    public function post($url, $request);
}
