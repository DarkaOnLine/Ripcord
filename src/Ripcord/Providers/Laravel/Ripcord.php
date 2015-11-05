<?php

namespace Ripcord\Providers\Laravel;

use Ripcord\Ripcord as RipcordBase;

class Ripcord
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $db;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;
    /**
     * @var int
     */
    protected $uid;

    /**
     * @var \Ripcord\Client\Client
     */
    protected $client;

    /**
     * Ripcord constructor.
     *
     * @param $url string
     * @param $db string
     * @param $user string
     * @param $password string
     */
    public function __construct($url, $db, $user, $password)
    {
        $this->url = $url;
        $this->db = $db;
        $this->user = $user;
        $this->password = $password;

        $this->connect();
    }

    /**
     * Create connection
     */
    public function connect()
    {
        $common = RipcordBase::client("$this->url/common");
        $this->uid = $common->authenticate($this->db, $this->username, $this->password, array());
        $this->client = RipcordBase::client("$this->url/object");
    }
}
