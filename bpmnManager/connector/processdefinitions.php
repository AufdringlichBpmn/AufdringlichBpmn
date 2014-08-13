<?php

require_once('_init.php');

$output->process_definitions = array();

foreach($store->listProcessDefinitions() as $processDefinition) {
	$output->process_definitions[] = array(
		"id"=>$processDefinition->getId(), // TODO
		"file" =>"",
	);
}

echo json_encode($output);