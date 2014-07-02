<?php

require_once('../../bpmnEngine/BpmnEngine.php');
require_once('../../bpmnEngine/FileStore.php');

$store = new FileStore();

$output->process_definitions = array();

foreach($store->listProcessDefinitions() as $processDefinition) {
	$output->process_definitions[] = array(
		"id"=>$processDefinition->getId(), // TODO
		"file" =>"",
	);
}

echo json_encode($output);