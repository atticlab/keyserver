<?php

/**
 * Created by PhpStorm.
 * User: skorzun
 * Date: 16.06.16
 * Time: 15:36
 */
namespace SWP\Services;

use \Basho\Riak;
use \Basho\Riak\Node;
use \Basho\Riak\Command;

class RiakDBService extends \Phalcon\DI\Injectable
{
    public $nodes;
    public $db;

    public function __construct($port, $hosts) {
        $this->nodes = (new Node\Builder)
            ->onPort($port)
            ->buildCluster($hosts);
        // instantiate the Riak client
        $this->db = new Riak($this->nodes);
    }
}