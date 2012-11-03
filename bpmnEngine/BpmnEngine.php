<?php

class BpmnEngine{
	private $db;

	function __construct($db) {
		$this->db = $db;

		//create the database
		try{
			$this->db->exec("
				CREATE TABLE process_definition
				(	id INTEGER PRIMARY KEY
					, name TEXT
					, xml TEXT
				);
				CREATE TABLE process_instance 
				(	id INTEGER PRIMARY KEY
					, process_definition_id INTEGER
					, created_ts DATETIME
					, executed_ts DATETIME
				);
				CREATE TABLE task
				(	id INTEGER PRIMARY KEY
					, process_instance_id INTEGER
					, ref_id TEXT
					, type TEXT
					, created_ts DATETIME
					, executed_ts DATETIME
					, retries INTEGER
					, exception_message TEXT
				);
				CREATE TABLE variable_map
				(	id INTEGER PRIMARY KEY
					, process_instance_id INTEGER
					, key TEXT
					, value TEXT
				);
			");
		}catch(Exception $e){}
	}

	public function importDefinition($file) {
		$definition = simplexml_load_file($file);
		foreach($definition->process as $process) {
			$statement = $this->db->prepare("INSERT INTO process_definition (name, xml) VALUES (:name, :xml)");
			$statement->execute(array(':name' => $process->attributes()->id, ':xml' => $definition->asXML()));
		}
	}
	
	public function startProcessByName($name, $variables) {
		return new ProcessInstance($this, $name, $variables);
	}
	
	public function executeStatement($sql, $parameters){
		$statement = $this->db->prepare($sql);
		$result = $statement->execute($parameters);
		return $this->db->lastInsertId();
	}

	public function getResult($sql, $parameters = array()){
		$statement = $this->db->prepare($sql);
		$statement->execute($parameters);
		return $statement->fetchAll();
	}
	
	public function getSingleResult($sql, $parameters = array(), $column = 0){
		$statement = $this->db->prepare($sql);
		$statement->execute($parameters);
		$result = $statement->fetchAll();
		foreach($result as $row){
			return $row[$column];
		}
	}

	function listUserTasks(){
		$userTasks = array();
		$result = $this->getResult("SELECT t.* FROM task t
			JOIN process_instance pi ON t.process_instance_id = pi.id AND pi.executed_ts IS NULL
			WHERE type='userTask' AND t.executed_ts IS NULL");
		foreach($result as $row){
			$processInstanceId = $row["process_instance_id"];
			$xml = $this->getProcessDefinition($processInstanceId);
			$elementId = $row["ref_id"];
			
			foreach($xml->xpath("//*[@id='".$elementId."']") as $element){
				$text = $element->documentation;
				$variables = $this->getResult("SELECT * FROM variable_map WHERE process_instance_id = :pi_id",array(":pi_id"=>$processInstanceId));
				foreach($variables as $var) {
					$text = str_replace('{'.$var["key"].'}', $var["value"], $text);
				}
				array_push($userTasks, array("id" => $row["id"]
					, "guid"=>$this->get($processInstanceId, "_guid")
					, "ref_id" => $elementId
					, "process_instance_id" => $processInstanceId
					, "text" => $text
				));
			}
		}
		return $userTasks;
	}
	
	function getProcessInstanceByGuid($guid){
		return new ProcessInstance($this, $guid);
	}
	
	function getProcessDefinition($processInstanceId){
		$xml = $this->getSingleResult( "
			SELECT xml FROM process_definition pd
			JOIN process_instance pi ON pd.id = pi.process_definition_id
			WHERE pi.id = :pi_id
		",array(":pi_id"=>$processInstanceId));
		$xml = new SimpleXMLElement($xml);
		$xml->registerXPathNamespace("bpmn", "http://www.omg.org/spec/BPMN/20100524/MODEL");
		return $xml;
	}
	
	function put($processInstanceId, $key, $value){
		$this->executeStatement("DELETE FROM variable_map 
			WHERE key=:key AND process_instance_id=:pi_id", 
			array(":key" => $key, ":pi_id" => $processInstanceId));
		$this->executeStatement("
			INSERT INTO variable_map (process_instance_id, key, value)
			VALUES ( :pi_id, :key, :value)
		",array(':pi_id' => $processInstanceId, ":key" => $key, ":value" => $value));
	}

	function get($processInstanceId, $key){
		return $this->getSingleResult("
			SELECT value FROM variable_map WHERE process_instance_id = :pi_id AND key = :key LIMIT 1
		", array(':pi_id' => $processInstanceId, ":key" => $key));
	}

	private static $bpmnElemenHandlerMap = array();
	static function registerBpmnElementHandler($name, $handler){
		self::$bpmnElemenHandlerMap[$name] = $handler;
	}
	function getBpmnElementHandler($name){
		return self::$bpmnElemenHandlerMap[$name];
	}
}
BpmnEngine::registerBpmnElementHandler('callActivity', new CallActivityHandler);
BpmnEngine::registerBpmnElementHandler('scriptTask', new ScriptTaskHandler);
BpmnEngine::registerBpmnElementHandler('serviceTask', new ServiceTaskHandler);
BpmnEngine::registerBpmnElementHandler('userTask', new UserTaskHandler);
BpmnEngine::registerBpmnElementHandler('startEvent', new DefaultBpmnElementHandler);
BpmnEngine::registerBpmnElementHandler('exclusiveGateway', new DefaultBpmnElementHandler);
BpmnEngine::registerBpmnElementHandler('parallelGateway', new ParallelGatewayHandler);
BpmnEngine::registerBpmnElementHandler('endEvent', new EndEventHandler);

class DefaultBpmnElementHandler{
	function createTaskInstance($processInstance, $element){
		return false;
	}
	function discoverTasks($processInstance, $element){
		$elementId = $element->attributes()->id;
		$default = $element->attributes()->default;
		$useDefault = true;
		// find sequence flows and create following tasks
		foreach($processInstance->getXml()
		->xpath("//bpmn:sequenceFlow[@sourceRef='".$elementId."'][@id!='".$default."']") as $sequenceFlow){
			// check expressions
			if($sequenceFlow->conditionExpression){
				$condition = $sequenceFlow->conditionExpression;
				$condition = preg_replace( "/\\$([a-zA-Z_1-9]+)/", "\$processInstance->get('$1')", $condition);
				if(!eval("return ".$condition.";")){
					continue;
				}
			}
			$processInstance->discoverTasks(
				$sequenceFlow->attributes()->targetRef);
			$useDefault = false;
		}
		if($useDefault && $default) foreach($processInstance->getXml()
		->xpath("//bpmn:sequenceFlow[@id='".$default."']") as $sequenceFlow){
			$processInstance->discoverTasks(
				$sequenceFlow->attributes()->targetRef);
		}
		return true;
	}
}
class TaskHandler extends DefaultBpmnElementHandler{
	function createTaskInstance($processInstance, $element){
		$processInstance->getBpmnEngine()->executeStatement( "
			INSERT INTO task(process_instance_id, ref_id, type, created_ts, retries)
			VALUES (:pi_id, :ref_id, :type, julianday('now'), 3);
		", array(":pi_id"=>$processInstance->getProcessInstanceId(),
			"ref_id"=>$element->attributes()->id,
			":type"=>$element->getName()));
		return 2;
	}
	function processTaskInstance($processInstance, $element, $taskId){
		$elementId = $element->attributes()->id;
		$processInstance->getBpmnEngine()->executeStatement(
			"UPDATE task SET retries = retries - 1 WHERE id=:id"
			, array(":id"=>$taskId));
		try{
			$this->evaluate($processInstance, $element);
			
			$processInstance->getBpmnEngine()->executeStatement(
				"UPDATE task SET retries = NULL, executed_ts = julianday('now') WHERE id=:id"
				, array(":id"=>$taskId));
			
			$processInstance->discoverTasks($elementId, true);

		}catch(Exception $e){
			$processInstance->getBpmnEngine()->executeStatement(
				"UPDATE task SET exception_message = :exception_message WHERE id=:id"
				, array(":id"=>$taskId, ":exception_message"=>$e->getMessage()));
		}
	}
}
class CallActivityHandler extends TaskHandler{
	protected function evaluate($processInstance, $element){
		$globalTaskId = $element->attributes()->calledElement;
		foreach($processInstance->getXml()->xpath("//bpmn:globalScriptTask[@id='".$globalTaskId."']") as $globalScriptTask){
			$script = $globalScriptTask->script;
			$context = $processInstance;
			eval($script);
		}
	}
}
class ScriptTaskHandler extends TaskHandler{
	protected function evaluate($processInstance, $element){
		$script = $element->script;
		$context = $processInstance;
		eval($script);
	}
}
class ServiceTaskHandler extends TaskHandler{
	protected function evaluate($processInstance, $element){
		$reflectionMethod = new ReflectionMethod(
			(string)$element->attributes()->implementation, 'processServiceTask');
		if($reflectionMethod){
			$reflectionMethod->invoke(null,
				$processInstance->getBpmnEngine(), 
				$processInstance->getProcessInstanceId(), 
				$element->attributes()->id);
		}
	}
}
class UserTaskHandler extends TaskHandler{
	function createTaskInstance($processInstance, $element){
		$taskId = $processInstance->getBpmnEngine()->executeStatement( "
			INSERT INTO task( process_instance_id, ref_id, type, created_ts)
			VALUES (:pi_id, :ref_id, :type, julianday('now'));
		", array(":pi_id"=>$processInstance->getProcessInstanceId(),
			"ref_id"=>$element->attributes()->id, 
			":type"=>$element->getName())
		);

		try{
			$reflectionMethod = new ReflectionMethod((string)$element->attributes()->implementation, 'preProcessUserTask');
			if($reflectionMethod){
				$reflectionMethod->invoke(null,
					$processInstance->getBpmnEngine(), 
					$processInstance->getProcessInstanceId(), 
					$element->attributes()->id);
			}
		}catch(Exception $e){
			$processInstance->getBpmnEngine()->executeStatement(
				"UPDATE task SET exception_message = :exception_message
					WHERE id=:id"
				, array(":id"=>$taskId, 
					":exception_message"=>$e->getMessage()));
		}
		return 2;
	}
}
class ParallelGatewayHandler extends DefaultBpmnElementHandler{
	function discoverTasks($processInstance, $element){
		$elementId = $element->attributes()->id;
		$isJoin = 1<count($processInstance->getXml()
			->xpath("//bpmn:sequenceFlow[@targetRef='".$elementId."']"));
		if($isJoin){
			// join ist done, wenn alle zuliefertasks done sind
			foreach($processInstance->getXml()
				->xpath("//bpmn:sequenceFlow[@targetRef='".$elementId."']") as $sequenceFlow){
				// Task noch nicht ausgeführt
				if($processInstance->getBpmnEngine()->getSingleResult(" SELECT COUNT(*) as count FROM task 
					WHERE process_instance_id=:pi_id AND ref_id = :ref_id AND executed_ts IS NULL"
					, array(":pi_id"=>$processInstance->getProcessInstanceId(),
						":ref_id"=>$sequenceFlow->attributes()->sourceRef))){
					return false; // kein weiteres Discover, um zu warten
				}
				// Task noch gar nicht entdeckt
				if(0==$processInstance->getBpmnEngine()->getSingleResult(" SELECT COUNT(*) as count FROM task 
					WHERE process_instance_id=:pi_id AND ref_id = :ref_id"
					, array(":pi_id"=>$processInstance->getProcessInstanceId(),
						":ref_id"=>$sequenceFlow->attributes()->sourceRef))){
					return false; // kein weiteres Discover, um zu warten
				}
			}
			// wenn kein offener Task gefunden, einfach weiterlaufen lassen
		}

		return parent::discoverTasks($processInstance, $element);
	}
}

class EndEventHandler extends DefaultBpmnElementHandler{
	function discoverTasks($processInstance, $element){
		$processInstance->getBpmnEngine()->executeStatement(
			"UPDATE task SET retries = NULL WHERE process_instance_id = :pi_id"
			,array(":pi_id"=>$processInstance->getProcessInstanceId())
		);
		$processInstance->getBpmnEngine()->executeStatement(
			"UPDATE process_instance SET executed_ts =  julianday('now') WHERE id = :pi_id"
			,array(":pi_id"=>$processInstance->getProcessInstanceId())
		);
		return true;
	}
}


class ProcessInstance{
	private $engine;
	private $xml;
	private $processInstanceId;
	
	function __construct($bpmnEngine, $name, $variables = null) {
		$this->engine = $bpmnEngine;
		
		if(null==$variables){
			// Reconstruct Object via GUID
			$this->processInstanceId = $this->engine->getSingleResult(
				"SELECT process_instance_id FROM variable_map 
					WHERE key='_guid' AND value=:guid", array(":guid"=>$name));
			$this->xml = $this->engine->getProcessDefinition($this->processInstanceId);

		} else{
			// Start new Process
			$this->processInstanceId = $this->engine->executeStatement( "
				INSERT INTO process_instance ( process_definition_id, created_ts)
				SELECT id, julianday('now')
				FROM process_definition WHERE name = :name ORDER BY id DESC LIMIT 1
			",array(":name"=>$name));
			
			$this->xml = $this->engine->getProcessDefinition($this->processInstanceId);
			
			foreach($variables as $key => $value) $this->put($key, $value);
			if( ! $this->get("_guid")){
				$this->put("_guid",sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X'
					, mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)
					, mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535)
					, mt_rand(0, 65535), mt_rand(0, 65535)));
			}
			
			foreach($this->xml->xpath("//bpmn:process/bpmn:startEvent") as $startEvent){
				$this->discoverTasks($startEvent->attributes()->id);
			}
		}
	}
	
	public function getProcessInstanceId(){
		return $this->processInstanceId;
	}
	
	function executeUserTaskByRefId($refId){
		if($this->engine->getSingleResult("SELECT id FROM task
			WHERE process_instance_id = :pi_id AND ref_id = :ref_id AND executed_ts IS NULL"
			, array(":pi_id"=>$this->processInstanceId, ":ref_id"=>$refId))){
			$this->engine->executeStatement("UPDATE task
				SET executed_ts = julianday('now')
				WHERE process_instance_id = :pi_id AND ref_id = :ref_id"
				, array(":pi_id"=>$this->processInstanceId, ":ref_id"=>$refId));
			$this->discoverTasks($refId, true);
		}
		while($this->processNextServiceTask());
	}
	
	function discoverTasks($elementId, $isExecuted = false){
		foreach($this->xml->xpath("//*[@id='".$elementId."']") as $element){
			$handler = $this->engine
				->getBpmnElementHandler($element->getName());
			if(!$isExecuted){
				if($handler->createTaskInstance($this, $element)){
					continue;
				}
			}

			if($handler->discoverTasks($this, $element)){
				continue;
			}else{
				break;
			}
		}
		while($this->processNextServiceTask());
	}
	
	function processNextServiceTask(){
		$result = $this->engine->getResult("SELECT * FROM task WHERE retries>0 LIMIT 1");
		foreach($result as $row){
			$elementId = $row["ref_id"];
			$taskId = $row["id"];
			foreach($this->xml->xpath("//*[@id='".$elementId."']") as $element){
				$handler = $this->engine
					->getBpmnElementHandler($element->getName());
				$handler->processTaskInstance($this, $element, $taskId);
			}
			return true;
		}
		return false;
	}

	function put($key, $value){
		$this->engine->put($this->processInstanceId, $key, $value);
	}

	function get($key){
		return $this->engine->get($this->processInstanceId, $key);
	}
	public function getBpmnEngine(){
		return $this->engine;
	}
	public function getXml(){
		return $this->xml;
	}
}
?>