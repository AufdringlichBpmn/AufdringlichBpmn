<?php

require_once('_init.php');

$processId = $_GET["process_id"];
$taskId = $_GET["task_id"];
$vars = json_decode($_GET["vars"]);
$result = $_GET["result"];

$p = $bpmnEngine->loadProcess($processId);

foreach($vars as $name => $value){
	$p->put($name, $value);
}

$bpmnEngine->executeUserTaskByTaskId($p, $taskId, $result);

echo json_encode(array());
