<?php
require __DIR__ . './../vendor/autoload.php';

use Elastica\Client;
use Elastica\Type\Mapping;

$elasticaClient = new Client();

// create index
$elasticaIndex = $elasticaClient->getIndex('recommendation');
$elasticaIndex->create();

//Create a type for deals
$elasticaType = $elasticaIndex->getType('dealitem');

// Define mapping
$properties = array(
    'deal_id'           => array('type' => 'long'),
    'title'             => array('type' => 'text'),
    'coupon_end_time'   => array('type' => 'integer'),
    'partner_id'        => array('type' => 'long')
);

$mapping = new Mapping();
$mapping->setType($elasticaType);
$mapping->setProperties($properties);

// Send mapping to type
$mapping->send();

// Create a type for a user
$elasticaType = $elasticaIndex->getType('user');

// Define mapping for user
$properties = array(
    'user_id'           => array('type'     => 'long'),
    'deal_title'        => array('type'     => 'text',
                                 'analyzer' => 'whitespace',
                                 'fields'   => [
                                     'keyword'  => [
                                         'type' => 'keyword'
                                     ]
                                 ]
                                ),
    'deal_count'        => array('type'     => 'integer')
);

$mapping = new Mapping();
$mapping->setType($elasticaType);
$mapping->setProperties($properties);

// Send mapping to type
$mapping->send();