<?php

require_once('_init.php');

$processId = $_GET["process_id"];
$taskId = $_GET["task_id"];
$result = $_GET["result"];

$p = $bpmnEngine->loadProcess($processId);
$bpmnEngine->executeUserTaskByTaskId($p, $taskId, $result);

echo json_encode(array());
