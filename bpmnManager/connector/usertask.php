<?php

require_once('_init.php');

$p = $bpmnEngine->loadProcess($_GET["process_id"]);
$t = $p->getTaskById($_GET["task_id"]);
$e = $p->findElementById($t->ref_id);

$variables = array();
foreach($p->variables as $key=>$value){
	$variables[] = array("key"=>$key,"value"=>$value);
}

$choice = array();
foreach($p->findSequenceFlowElementsBySourceElement($e) as $s){
	$name = (String)$s->attributes()->name;
	$isDefault = $name == null;
	$choice[] = array(
		"sequenceflow" => $s,
		"name" => $name,
		"default" => $isDefault
	);
}

$documentation = array();
foreach($e->documentation as $documentationElement){
	$d = (String)$documentationElement;
	// apply Mustache
	foreach($p->variables as $name => $value){
		$d = preg_replace("/\{\{$name\}\}/", $value, $d);
	}
	$documentation[] = $d;
}

$ioSpecification = array();
if(isSet($e->ioSpecification)){
	foreach($e->ioSpecification->dataInput as $dataInput){
		$dataInputName = (String)$dataInput->attributes()->name;
		$ioSpecification[] = array(
			"input"=>array(
				"name"=>$dataInputName,
				"value"=>$p->get($dataInputName)
			)
		);
	}
	foreach($e->ioSpecification->dataOutput as $dataOutput){
		$dataOutputName = (String)$dataOutput->attributes()->name;
		$ioSpecification[] = array(
			"output" => array(
				"name"=>$dataOutputName,
				"value"=>$p->get($dataOutputName)
			)
		);
	}
}

echo json_encode(array("usertask" => array(
	"name" => (String)$e->attributes()->name,
	"created_ts" => $t->createdTs,
	"process_definition_id" => $p->processDefinitionId,
	"process_id" => $p->getId(),
	"variables" => $variables,
	"choice" => $choice,
	"documentation" => $documentation,
	"ioSpecification"=>$ioSpecification,
	"id" => $t->_id
)));

