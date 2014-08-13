<?php

require_once('_init.php');

$processDefinitionId = $_GET["process_definition_id"];
$processId = $_GET["process_id"];
$taskId = $_GET["task_id"];
$result = $_GET["result"];

$p = $bpmnEngine->loadProcess($processId);
$bpmnEngine->executeUserTaskByTaskId($p, $taskId, $result);

var_dump($p);

echo json_encode(array());
