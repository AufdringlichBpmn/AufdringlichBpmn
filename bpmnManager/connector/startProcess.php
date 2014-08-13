<?php

require_once('_init.php');

$processDefinitionId = $_GET["process_definition_id"];
$valueMap = array();
$p = $bpmnEngine->startProcess($processDefinitionId, $valueMap);
echo json_encode(array("process"=>$p));

