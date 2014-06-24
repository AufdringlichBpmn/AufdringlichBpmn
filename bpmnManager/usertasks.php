<?php
require_once '../BpmnEngine.php';
require_once '../FileStore.php';
require_once '../XmlAdapter.php';


$bpmnEngine = new BpmnEngine(new FileStore(), "TASKS_TEST");

$valueMap = array("visits" => "start");
$process = $bpmnEngine->startProcess($valueMap);
$processId = $process->getId();
$process = $bpmnEngine->loadProcess($processId);
$bpmnEngine->executeUserTaskByRefId($process, "_19", "success");
print_r($bpmnEngine);	