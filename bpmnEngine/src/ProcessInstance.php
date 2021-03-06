<?php

class ProcessInstance extends \dto\Process{
	private $engine;
	private $xmlAdapter;

	public $variables;
	public $type;
	public $processDefinitionId;
	public $created_ts;

	function __construct($bpmnEngine, $xmlAdapter = null) {
		$this->engine = $bpmnEngine;
		$this->xmlAdapter = $xmlAdapter===null?new XmlAdapter():$xmlAdapter;
	}

	static function buildByDto($bpmnEngine, $process_definition_xml, $processDto){
		$processInstance = new ProcessInstance($bpmnEngine);
		$processInstance->merge($processDto);
		$processInstance->xmlAdapter->setProcessDefinitionXml($process_definition_xml);
		return $processInstance;
	}

	static function buildByProcessDefinition($bpmnEngine, $processDefinition){
		$processInstance = new ProcessInstance($bpmnEngine);
		$processInstance->_id = $processDefinition->getId().":".md5(''.time());
		$processInstance->type = "process_instance";
		$processInstance->variables = new \dto\VariableMap();
		$processInstance->processDefinitionId = $processDefinition->getId();
		$processInstance->xmlAdapter->setProcessDefinitionXml($processDefinition->xml);
		$processInstance->created_ts = time();
		return $processInstance;
	}

	function start(){
		$startEventElement = $this->xmlAdapter->findStartEventElement();
		$this->discoverTasks($this->getAttribute($startEventElement, 'id'), null);
	}

	function findElementById($elementId){
		return $this->xmlAdapter->findElementById($elementId);
	}
	
	function findSequenceFlowElementsBySourceElementExcludeDefault($element, $defaultId){
		return $this->xmlAdapter->findSequenceFlowElementsBySourceElementExcludeDefault($element, $defaultId);
	}

	function findSequenceFlowElementsBySourceElement($element){
		return $this->xmlAdapter->findSequenceFlowElementsBySourceElement($element);
	}
	
	function findBoundaryEventElementsByRefElement($element){
		return $this->xmlAdapter->findBoundaryEventElementsByRefElement($element);
	}
	
	function findSequenceFlowElementsByTargetElement($element){
		return $this->xmlAdapter->findSequenceFlowElementsByTargetElement($element);
	}

	public function getAttribute($element, $attribute) {
		return $this->xmlAdapter->getAttribute($element, $attribute) ;
	}
	
	public function getAttributes($element) {
		return $this->xmlAdapter->getAttributes($element);
	}
	
	public function getName($element) {
		return $this->xmlAdapter->getName($element) ;
	}

	function discoverTasks($elementId, $value, $isExecuted = false){
		$element = $this->findElementById($elementId);
		$handler = $this->getBpmnElementHandler($this->getName($element));
		if( (!$isExecuted) && $handler->createTaskInstance($this, $element) ){
			// TODO Boundary Event anlegen
			foreach($this->findBoundaryEventElementsByRefElement($element) as $boundaryElement){
				$boundaryHandler = $this->getBpmnElementHandler($this->getName($boundaryElement));
				$boundaryHandler->createEventInstance($this, $boundaryElement);
			}
			//
		}else if( (!$isExecuted) && $handler->createEventInstance($this, $element) ){
			//
		}else if($handler->discoverTasks($this, $value, $element)){
			//
		}else{
			return;
		}
		while($this->processNextServiceTask());
	}
	
	private function getBpmnElementHandler($elementName){
		global $CONFIG;
		foreach($CONFIG->elementHandlers as $handler){
			$handlerInstance = new $handler;
			if($handler::canHandleElement($elementName))
				return $handlerInstance;
		}
		throw new Exception("No Handler found for $elementName.");
	}
	
	function processNextServiceTask(){
		$task = $this->findNextServiceTask();
		if($task){
			$elementId = $task->getRefId();
			$taskId = $task->getId();
			if($element = $this->findElementById($elementId)){
				$handler = $this->getBpmnElementHandler($this->getName($element));
				$handler->processTaskInstance($this, $element, $taskId);
			}
			return true;
		}
		return false;
	}

	function put($key, $value){
		$this->variables->$key = $value;
	}
	function get($key){
		return isSet($this->variables->$key)?$this->variables->$key:null;
	}

// trait TaskAdapterTrait{
	public $seq_taskId = 0;
	public $tasks = array();
	public $events = array();
	
	public function getProcessInstanceId(){
		return $this->_id;
	}
	
	function getTaskResult($element){
		$task = $this->getTaskByRefId($this->getAttribute($element, 'id'));
		return $task->result;
	}

	function addTask($task){
		$this->seq_taskId++;
		$task->_id = $this->seq_taskId;
		$this->tasks[] = $task;
		return $task->_id;
	}

	function addEvent($event){
		$this->seq_taskId++;
		$event->_id = $this->seq_taskId;
		$this->events[] = $event;
		return $event->_id;
	}

	function decrementTaskRetries($taskId){
		$task = $this->getTaskById($taskId);
		$task->retries--;
	}
	
	public function getTaskById($taskId){
		foreach($this->tasks as $i => $task)
			if($task->_id == $taskId)
				return $task;
		throw new Exception("Task $taskId not found");
	}
	
	public function getTaskByRefId($refId){
		foreach($this->tasks as $i => $task)
			if($task->ref_id == $refId)
				return $task;
		return false;
	}
		
	function markTaskExecuted($taskId, $result){
		$task = $this->getTaskById($taskId);
		$task->executedTs = time();
		$task->result = $result;
	}
	
	function setTaskExceptionMessage($taskId, $message){
		$task = $this->getTaskById($taskId);
		$task->exceptionMessage = $message;
	}
	
	public function executeTaskByRefId($refId, $result = null){
		$task = $this->getTaskByRefId($refId);
		$this->executeTask($task, $result);
	}
	
	public function executeTaskByTaskId($taskId, $result = null){
		$task = $this->getTaskById($taskId);
		$this->executeTask($task, $result);
	}
	
	private function executeTask($task, $result = null){
		$task->executedTs = time();
		$task->result = $result;
		$this->discoverTasks($task->ref_id, $result, true);
		while($this->processNextServiceTask());
	}
	
	public function evaluateUserTask($taskId, $result = null){
		$task = $this->getTaskById($taskId);
		$task->result = $result;
	}
	
	public function processNextUserTask(){
		foreach($this->tasks as $i => $task) {
			if($task->type == "userTask" && !isSet($task->executedTs) && isSet($task->result)) {
				$this->executeUserTaskByRefId($task->ref_id, $task->result);
				return true;
			}
		}
		return false;
	}

	public function getEventByRefId($refId){
		foreach($this->events as $i => $event)
			if($event->ref_id == $refId)
				return $event;
		return false;
	}
		
	public function executeEventByRefId($refId, $result = null){
		$task = $this->getEventByRefId($refId);
		$task->executedTs = time();
		$task->result = $result;
		$this->discoverTasks($refId, $result, true);
		while($this->processNextServiceTask());
	}
	
	public function processNextEvent(){
		foreach($this->events as $i => $event){
			if(!isSet($event->executedTs)){
				$handler = $this->getBpmnElementHandler($event->type);
				if( $handler->isEventOccured($this, $event) ) {
					$this->executeEventByRefId($event->ref_id, $event->result);
					return true;
				}
			}
		}
		return false;
	}

	function markProcessInstanceExecuted($result){
		$this->executedTs = time();
		$this->result = $result;
	}
	
	public function getResult() {
		if(isSet($this->result))
			return $this->result;
	}
	
	private function findNextServiceTask(){
		foreach($this->tasks as $i => $task)
		if($task->retries > 0 && !isSet($task->executedTs))
			return $task;
	}
}
