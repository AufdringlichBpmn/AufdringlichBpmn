<?php

include __DIR__.'/../../bpmnEngine/build/bpmn.phar';

$store = new \persistence\FileStore();

$output->process_definitions = array();

foreach($store->listProcessDefinitions() as $processDefinition) {
	$output->process_definitions[] = array(
		"id"=>$processDefinition->getId(), // TODO
		"file" =>"",
	);
}

echo json_encode($output);