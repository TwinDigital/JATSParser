<?php

require __DIR__ . '/../vendor/autoload.php';

$inputFolder  = __DIR__ . '/../files/input';
$outputFolder = __DIR__ . '/../files/output';

$collection = new \JATSCollector\Collection($inputFolder, $outputFolder);
