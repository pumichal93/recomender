<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 9.12.2017
 * Time: 15:21
 */

namespace src;

use Elastica\Client;

class Connect
{

    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

}