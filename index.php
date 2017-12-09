<?php
require __DIR__ . '/vendor/autoload.php';
use src\InitializeElastic;

$initialize = new InitializeElastic();
$a = $initialize->createMapping();
$a = 1;