<?php

require_once('../../bpmnEngine/BpmnEngine.php');
require_once('../../bpmnEngine/XmlAdapter.php');
require_once('../../bpmnEngine/FileStore.php');

$store = new FileStore();

$output = array();
foreach($store->findOpenUserTasks() as $row){
	$engine = new BpmnEngine($store, $row["processDefinitionId"]);
	$p = $engine->loadProcess($row["processId"]);
	$t = $p->getTaskById($row["taskId"]);
	$e = $p->findElementById($t->ref_id);
	
	$output[]= array(
		"name"=>(String)$e->attributes()->name,
		"created_ts"=>$t->createdTs,
		"process_definition_id"=>$row["processDefinitionId"],
		"process_id"=>$row["processId"],
		"id"=>$t->_id
	);
}

echo json_encode(array("usertasks"=>$output));
