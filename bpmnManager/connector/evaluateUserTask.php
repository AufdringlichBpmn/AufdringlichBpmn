<?php

require_once('../../bpmnEngine/BpmnEngine.php');
require_once('../../bpmnEngine/XmlAdapter.php');
require_once('../../bpmnEngine/FileStore.php');

$store = new FileStore();

$processDefinitionId = $_GET["process_definition_id"];
$processId = $_GET["process_id"];
$taskId = $_GET["task_id"];
$result = $_GET["result"];

$engine = new BpmnEngine($store, $processDefinitionId);
$p = $engine->loadProcess($processId);

$p->evaluateUserTask($taskId, $result);


echo json_encode(array());
