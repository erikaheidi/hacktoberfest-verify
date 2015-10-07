<?php
/**
 * Redis cache service
 */

namespace AppBundle\Service;

use Predis\Client;

class RedisCacheService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function save($key, $content)
    {
        $this->client->set($key, serialize($content));
    }

    public function fetch($key)
    {
        return unserialize($this->client->get($key));
    }
}
