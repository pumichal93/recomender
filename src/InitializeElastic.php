<?php

namespace src;

use Elastica\Type\Mapping;
class InitializeElastic extends Connect
{

    public function createMapping() {

        // Create index

        $elasticaIndex = $this->client->getIndex('recommendation');
        $elasticaIndex->create();

        //Create a type
        $elasticaType = $elasticaIndex->getType('dealitem');

        // Define mapping
        $properties = array(
            'id'                => array('type' => 'integer'),
            'deal_id'           => array('type' => 'integer'),
            'title_dealitem'    => array('type' => 'keyword'),
            'coupon_end_time'   => array('type' => 'integer')
        );

        $mapping = new Mapping();
        $mapping->setType($elasticaType);
        $mapping->setProperties($properties);

        // Send mapping to type
        $mapping->send();
    }

}