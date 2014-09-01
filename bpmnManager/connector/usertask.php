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
	foreach($e->ioSpecification->children() as $dataIo){
		$dataIoElementName = $dataIo->getName();
		$dataIoName = (String)$dataIo->attributes()->name;
		$itemSubjectRef = (String)$dataIo->attributes()->itemSubjectRef;
		$itemDefinition = $p->findElementById($itemSubjectRef);
		if($dataIoElementName == 'dataInput') {
			$html = null;
			if($itemDefinition) {
				foreach ( get_declared_classes() as $c ) {
					$class = new ReflectionClass($c);
					if ( $class->isSubclassOf('\elements\AbstractItemDefinitionImpl') && $class->IsInstantiable()) {
						$impl = $c;
						if($impl::canHandleItem($p, $itemDefinition)){
							$itemDefinitionImpl = new $impl;
							$itemDefinitionImpl->init($bpmnEngine, $p, $e);
							$html = $itemDefinitionImpl->asHtml();
						}
					}
				}
			}
			$ioSpecification[] = array(
				"input"=>array(
					"name"=>$dataIoName,
					"value"=>$p->get($dataIoName),
					"html"=>$html
				)
			);
		}
		else if ($dataIoElementName == 'dataOutput') {
			$html = null;
			if($itemDefinition) {
				foreach ( get_declared_classes() as $c ) {
					$class = new ReflectionClass($c);
					if ( $class->isSubclassOf('\elements\AbstractItemDefinitionImpl') && $class->IsInstantiable()) {
						$impl = $c;
						if($impl::canHandleItem($p, $itemDefinition)){
							$itemDefinitionImpl = new $impl;
							$itemDefinitionImpl->init($bpmnEngine, $p, $e);
							$html = $itemDefinitionImpl->asInput();
						}
					}
				}
			}
			$ioSpecification[] = array(
				"output" => array(
					"name"=>$dataIoName,
					"value"=>$p->get($dataIoName),
					"type_".((String)$dataIo->attributes()->itemSubjectRef)=>true,
					"html"=>$html
				)
			);
		}
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

