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
     * @param $config array
     */
    public function __construct($config = [])
    {
        $this->url = isset($config['url']) ? $config['url'] : config('ripcord.url');
        $this->db = isset($config['db']) ? $config['db'] : config('ripcord.db');
        $this->username = isset($config['user']) ? $config['user'] : config('ripcord.user');
        $this->password = isset($config['password']) ? $config['password'] : config('ripcord.password');

        $this->connect();
    }

    /**
     * Create connection.
     */
    public function connect()
    {
        $common = RipcordBase::client("$this->url/common");
        $this->uid = $common->authenticate($this->db, $this->username, $this->password, []);
        $this->client = RipcordBase::client("$this->url/object");
    }
}
