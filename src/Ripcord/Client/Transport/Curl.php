<?php

namespace Ripcord\Client\Transport;

use Ripcord\Client\Contracts\Transport;
use Ripcord\Exceptions\TransportException;
use Ripcord\Ripcord;

/**
 * This class implements the Transport interface using CURL.
 */
class Curl implements Transport
{
    /**
     * The curl handle.
     */
    private $curl;

    /**
     * A list of CURL options.
     */
    private $options = [];

    /**
     * A flag that indicates whether or not we can safely pass the previous exception to a new exception.
     */
    private $skipPreviousException = false;

    /**
     * Contains the headers sent by the server.
     */
    public $responseHeaders = null;

    /**
     * This is the constructor for the Ripcord_Transport_CURL class.
     *
     * @param array $curlOptions A list of CURL options.
     */
    public function __construct($curlOptions = null)
    {
        if (isset($curlOptions)) {
            $this->options = $curlOptions;
        }
        $version = explode('.', phpversion());
        if (((0 + $version[0]) == 5) && (0 + $version[1]) < 3) { // previousException supported in php >= 5.3
            $this->skipPreviousException = true;
        }
    }

    /**
     * This method posts the request to the given url.
     *
     * @param string $url     The url to post to.
     * @param string $request The request to post.
     *
     * @throws TransportException (ripcord::cannotAccessURL) when the given URL cannot be accessed for any reason.
     *
     * @return string The server response
     */
    public function post($url, $request)
    {
        if (!$this->curl) {
            $this->curl = curl_init();
        }
        curl_reset($this->curl);
        $options = (array) $this->options + [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $request,
            CURLOPT_HEADER         => true,
        ];
        curl_setopt_array($this->curl, $options);
        $contents = curl_exec($this->curl);
        $headerSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $this->responseHeaders = substr($contents, 0, $headerSize);
        $contents = substr($contents, $headerSize);

        if (curl_errno($this->curl)) {
            $errorNumber = curl_errno($this->curl);
            $errorMessage = curl_error($this->curl);
            $version = explode('.', phpversion());
            if (!$this->skipPreviousException) { // previousException supported in php >= 5.3
                $exception = new TransportException(
                    'Could not access '.$url,
                    Ripcord::CANNOT_ACCESS_URL,
                    new \Exception($errorMessage, $errorNumber)
                );
            } else {
                $exception = new TransportException(
                    'Could not access '.$url.' ( original CURL error: '.$errorMessage.' ) ',
                    Ripcord::CANNOT_ACCESS_URL
                );
            }

            throw $exception;
        }

        return $contents;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions($curlOptions = null)
    {
        if (isset($curlOptions)) {
            $this->options = $curlOptions;
        }
    }

    public function __destruct()
    {
        if ($this->curl) {
            curl_close($this->curl);
        }
    }
}
