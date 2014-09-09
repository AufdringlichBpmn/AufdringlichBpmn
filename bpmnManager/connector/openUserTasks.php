<?php

require_once('_init.php');

$output = array();
foreach($store->findOpenUserTasks() as $row){
	$p = $bpmnEngine->loadProcess($row["processId"]);
	$t = $p->getTaskById($row["taskId"]);
	$e = $p->findElementById($t->ref_id);
	
	$output[]= array(
		"title"=>$p->title,
		"name"=>(String)$e->attributes()->name,
		"created_ts"=>$t->createdTs,
		"process_definition_id"=>$row["processDefinitionId"],
		"process_id"=>$row["processId"],
		"id"=>$t->_id
	);
}
sort($output);

echo json_encode(array("usertasks"=>$output));

