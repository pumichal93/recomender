<?php
require __DIR__ . '/vendor/autoload.php';
use src\InsertToElastic;

$insert = new InsertToElastic('recommendation', 'dealitem', 'user');
//$insert->insertDeals();
$insert->insert();
