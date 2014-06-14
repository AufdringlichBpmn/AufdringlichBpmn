<?php
require_once ('elements/DefaultElementHandler.php');

class BpmnEngine{
	private $dbAdapter;
	private $xmlAdapter;
	private $name;

	function __construct($dbAdapter, $name) {
		$this->dbAdapter = $dbAdapter;
		$this->name = $name;
		$this->xmlAdapter = new XmlAdapter();
	}

	public function importDefinition($process_definition_xml) {
		$this->dbAdapter->importDefinition($process_definition_xml);
	}

	public function startProcess($variables) {
		$processDefinition = $this->dbAdapter->loadProcessDefinition($this->name);
		$process = ProcessInstance::buildByProcessDefinition($this, $processDefinition, $this->name);
		foreach($variables as $key => $value){
			$process->put($key,$value);
		}
		$process->start();
		$this->dbAdapter->storeProcess($process);
		return $process;
	}
	public function executeUserTaskByRefId($process, $refId, $value = null){
		$process->executeUserTaskByRefId($refId, $value);
		$this->dbAdapter->storeProcess($process);
	}
	function loadProcess($processId){
		$processDefinition = $this->dbAdapter->loadProcessDefinition($this->name);
		$processDto = $this->dbAdapter->loadProcess($processId);
		$process = ProcessInstance::buildByDto($this, $processDefinition->xml, $processDto);
		return $process;
	}
	function continueProcess($processId){
		$process = $this->loadProcess($processId);
		while($process->processNextUserTask()); // all evaluated usertasks
		while($process->processNextEvent()); // all evaluated events 
		$this->dbAdapter->storeProcess($process);
		return $process;
	}

	private static $bpmnElemenHandlerMap = array();
	static function registerBpmnElementHandler($name, $handler){
		self::$bpmnElemenHandlerMap[$name] = $handler;
	}
	function getBpmnElementHandler($name){
		print "\n".$name.":\n";
		return self::$bpmnElemenHandlerMap[$name];
	}

	function findNotExecutedProcessInstanceIds(){
		// TODO filter nach ProcessDefinition
		return $this->dbAdapter->findNotExecutedProcessInstanceIds();
	}
}

class VariableMap {}

class ProcessInstance extends Process{
	private $engine;
	private $xmlAdapter;

	public $variables;

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

	static function buildByProcessDefinition($bpmnEngine, $processDefinition, $name){
		$processInstance = new ProcessInstance($bpmnEngine);
		$processInstance->variables = new VariableMap();
		$processInstance->_id = $name.":".md5(''.time());
		$processInstance->type = "process_instance";
		$processInstance->xmlAdapter->setProcessDefinitionXml($processDefinition->xml);
		$processInstance->created_ts = time();
		return $processInstance;
	}

	function start(){
		$this->discoverTasks($this->getAttribute($this->xmlAdapter->findStartEventElement(), 'id'), null);
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
		if($element = $this->findElementById($elementId)) {
			$handler = $this->getBpmnElementHandler($element);
			if( (!$isExecuted) && ( $handler->createTaskInstance($this, $element) || $handler->createEventInstance($this, $element)) ){
				//
			}else if($handler->discoverTasks($this, $value, $element)){
				//
			}else{
				return;
			}
		}
		while($this->processNextServiceTask());
	}
	
	private function getBpmnElementHandler($element){
		return $this->engine->getBpmnElementHandler($this->getName($element));
	}
	
	function processNextServiceTask(){
		$task = $this->findNextServiceTask();
		if($task){
			$elementId = $task->getRefId();
			$taskId = $task->getId();
			if($element = $this->findElementById($elementId)){
				$handler = $this->getBpmnElementHandler($element);
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
	
	private function getTaskById($taskId){
		foreach($this->tasks as $i => $task)
			if($task->_id == $taskId)
				return $task;
		throw new Exception("Task $taskId not found");
	}
	
	private function getTaskByRefId($refId){
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
	
	public function executeUserTaskByRefId($refId, $result = null){
		$task = $this->getTaskByRefId($refId);
		$task->executedTs = time();
		$task->result = $result;
		$this->discoverTasks($refId, $result, true);
		while($this->processNextServiceTask());
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

	private function getEventByRefId($refId){
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
				$handler = $this->engine->getBpmnElementHandler($event->type);
				if( $handler->isEventOccured($this, $event) ) {
					$this->executeEventByRefId($event->ref_id, $event->result);
					return true;
				}
			}
		}
		return false;
	}

	function checkParallelGateReady($refId){
		$task = $this->getTaskByRefId($refId);
		if($task) return isSet($task->executedTs);
		return false;
	}
	
	function markProcessInstanceExecuted($result){
		$this->executedTs = time();
		$this->result = $result;
	}
	
	function isJoin($element){
		return 1<count($this->findSequenceFlowElementsByTargetElement($element));
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

interface ProcessStore {
	function importDefinition($simplexml);
	function loadProcessDefinition($name);
	function storeProcess($process);
	function loadProcess($processId);
	function findNotExecutedProcessInstanceIds();
}

class DbObject{
	public function __construct($dto = array()){
		$this->merge($dto);
	}
	public function merge($dto){
		foreach((array)$dto as $key => $value){
			if("\0" != substr($key, 0, 1))
				$this->$key = $value;
		}
	}
	public function getRefId(){
		return $this->ref_id;
	}
	public function getId(){
		return isSet($this->_id) ? $this->_id : null;
	}
}
class Task extends DbObject{
}
class Event extends DbObject{
}
class ProcessDefinition extends DbObject{
}
class Process extends DbObject{
	public function put($key, $value){
		$this->variables = (array)$this->variables;
		$this->variables[$key] = $value;
	}
	public function get($key){
		return isSet($this->variables->$key) ? $this->variables->$key : null;
	}
}
