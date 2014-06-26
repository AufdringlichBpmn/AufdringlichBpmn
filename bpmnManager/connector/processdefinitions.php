<?php

require_once('../../bpmnEngine/BpmnEngine.php');
require_once('../../bpmnEngine/FileStore.php');

$store = new FileStore();

$output->process_definitions = array();

foreach($store->listProcessDefinitions() as $processDefinition) {
	$pd->id = $processDefinition->getId(); // TODO
	$pd->file = $processDefinition->getRefId(); // TODO
	$output->process_definitions[] = $pd;
}

echo json_encode($output);