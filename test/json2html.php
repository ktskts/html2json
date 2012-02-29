<?php
require_once '../src/json2html.class.php';
$jsonFile = 'json/1.json';
$startTime = microtime(true);
$json = file_get_contents($jsonFile);
$instance = new JSON2HTML($json);
$result = $instance->run();
echo $result;
$endTime = microtime(true);
echo ($endTime - $startTime);