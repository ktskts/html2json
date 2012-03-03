<?php
require_once '../src/json2html.class.php';
$startTime = microtime(true);
$json = $_POST['content'];
if (get_magic_quotes_gpc()){
	$json = stripslashes($json);
}
$startTime = microtime(true);
$instance = new JSON2HTML($json);
$result = $instance->run();
$endTime = microtime(true);
$time = ($endTime - $startTime)*1000;
$time = number_format($time, 2);
$output = array('time'=> $time, 'text'=> $result);
echo json_encode($output);