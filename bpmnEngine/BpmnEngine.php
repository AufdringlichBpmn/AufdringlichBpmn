<?php
require_once("../XmlAdapter.php");

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
		$processDto = $this->dbAdapter->loadProcess($processId);
		$process = ProcessInstance::buildByDto($this, $processDto);
		return $process;
	}
	function continueProcess($processId){
		$processDto = $this->dbAdapter->loadProcess($processId);
		$process = ProcessInstance::buildByDto($this, $processDto);
		while($process->processNextUserTask());
		$this->dbAdapter->storeDbObject($process);
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
BpmnEngine::registerBpmnElementHandler('startEvent', new DefaultBpmnElementHandler);
BpmnEngine::registerBpmnElementHandler('endEvent', new EndEventHandler);

BpmnEngine::registerBpmnElementHandler('callActivity', new CallActivityHandler);
BpmnEngine::registerBpmnElementHandler('scriptTask', new ScriptTaskHandler);
BpmnEngine::registerBpmnElementHandler('serviceTask', new ServiceTaskHandler);
BpmnEngine::registerBpmnElementHandler('userTask', new UserTaskHandler);
BpmnEngine::registerBpmnElementHandler('subProcess', new SubProcessHandler);

BpmnEngine::registerBpmnElementHandler('exclusiveGateway', new ExclusivGatewayHandler);
BpmnEngine::registerBpmnElementHandler('parallelGateway', new ParallelGatewayHandler);

class DefaultBpmnElementHandler{
	function createTaskInstance($processInstance, $element){
		return false;
	}
	function discoverTasks($processInstance, $value, $element){
		print_r("discover $value");
		$default =  $processInstance->getAttribute($element, "default");
		$isExclusivGateway = $processInstance->getName($element) == "exclusivGateway";
		$isExclusivGatewayCondition = $value."?" ==  $processInstance->getAttribute($element, "name");
		$useDefault = true;
		// find sequence flows and create following tasks
		foreach($processInstance->findSequenceFlowElementsBySourceElementExcludeDefault($element, $default) as $sequenceFlow){
			// check expressions
			if($condition = $processInstance->getAttribute($sequenceFlow, "name")){
				if($value != $condition){
					continue;
				}
			}
			$processInstance->discoverTasks( $processInstance->getAttribute($sequenceFlow, "targetRef"), $value);
			$useDefault = false;
		}
		if($useDefault && $default && $sequenceFlow = $processInstance->findElementById($default)) {
			$processInstance->discoverTasks( $processInstance->getAttribute($sequenceFlow, "targetRef"), $value);
		}
		return true;
	}
}
class ExclusivGatewayHandler extends DefaultBpmnElementHandler{
	function discoverTasks($processInstance, $value, $element){
		if($processInstance->isJoin($element)){
			return parent::discoverTasks($processInstance, $value, $element);
		}else{
			$xvalue = ($value."?" == $processInstance->getAttribute($element, 'name')) ? "yes" : "no";
			// find sequence flows and create following tasks
			foreach($processInstance->findSequenceFlowElementsBySourceElement($element) as $sequenceFlow){
				// check expressions
				if($xvalue == $processInstance->getAttribute($sequenceFlow, 'name')){
					$processInstance->discoverTasks($processInstance->getAttribute($sequenceFlow, 'targetRef'), $value);
				}
			}
		}
		return true;
	}
}
class TaskHandler extends DefaultBpmnElementHandler{
	function createTaskInstance($processInstance, $element){
		$processInstance->createTaskInstance($element);
		return 2;
	}
	function processTaskInstance($processInstance, $element, $taskId){
		$elementId = $processInstance->getAttribute($element, "id");
		$processInstance->decrementTaskRetries($taskId);
		try{
			$result = $this->evaluate($processInstance, $element);
			$processInstance->markTaskExecuted($taskId, $result);
			$processInstance->discoverTasks($elementId, $result, true);
		}catch(Exception $e){
			print_r($e->getMessage());
			$processInstance->setTaskExceptionMessage($taskId, $e->getMessage());
		}
	}
}
class CallActivityHandler extends TaskHandler{
	protected function evaluate($processInstance, $element){
		$globalTaskId = $processInstance->getAttribute($element, 'calledElement');
		$globalScriptTask = $processInstance->findElementById($globalTaskId);
		$script = $globalScriptTask->script;
		$context = $processInstance;
		return eval($script);
	}
}
class ScriptTaskHandler extends TaskHandler{
	protected function evaluate($processInstance, $element){
		$script = $element->script;
		$context = $processInstance;
		return eval($script);
	}
}
class SubProcessHandler extends DefaultBpmnElementHandler{
	function processTaskInstance($processInstance, $element, $taskId){
		$subProcess = $processInstance->createSubProcessInstance($element);
		$subProcess->discoverTasks($subProcess->xmlAdapter->findStartEventElement($element), null);
		if(isSet($subProcess->executedTs)){
			$elementId = $processInstance->getAttribute($element, "id");
			$processInstance->discoverTasks($elementId, $subProcess->result, true);
		}
	}
}
class ServiceTaskHandler extends TaskHandler{
	protected function evaluate($processInstance, $element){
		$classname = $processInstance->getAttribute($element, 'implementation');
		if( ! class_exists($classname)) throw new Exception("Implementation nicht gefunden: ".$classname);
		$class = new ReflectionClass( $classname);
		$serviceTaskImpl = $class->newInstance();
		$serviceTaskImpl->init($processInstance,$element);
		return $serviceTaskImpl->processServiceTask();
	}
}
class UserTaskHandler extends TaskHandler{
	function createTaskInstance($processInstance, $element){
		$taskId = $processInstance->createUserTaskInstance($element);
		try{
			$reflectionMethod = new ReflectionMethod((string)$element->attributes()->implementation, 'preProcessUserTask');
			if($reflectionMethod){
				$reflectionMethod->invoke(null,
						$processInstance,
						$processInstance->getProcessInstanceId(),
						$processInstance->getAttribute($element, 'id')
				);
			}
		}catch(Exception $e){
			print_r($e);
			$processInstance->setTaskExceptionMessage($taskId, $e->getMessage());
		}
		return 2;
	}
	function evaluate($processInstance, $element){
		return $processInstance->getTaskResult($element);
	}
}
class ParallelGatewayHandler extends DefaultBpmnElementHandler{
	function discoverTasks($processInstance, $value, $element){
		if($processInstance->isJoin($element)){
			foreach($processInstance->findSequenceFlowElementsByTargetElement($element) as $sequenceFlow){
				$refId = $processInstance->getAttribute($sequenceFlow, 'sourceRef');
				if( ! $processInstance->checkParallelGateReady( $refId ))
					return false;
			}
		}
		return parent::discoverTasks($processInstance, $value, $element);
	}
}
class EndEventHandler extends DefaultBpmnElementHandler{
	function discoverTasks($processInstance, $value, $element){
		$result = $processInstance->getAttribute($element, 'name');
		$processInstance->markProcessInstanceExecuted($result);
		return false;
	}
}

class VariableMap {}

class ProcessInstance extends Process{
	private $engine;
	private $xmlAdapter;

	public $variables;

	static function buildByDto($bpmnEngine, $processDto){
		$processInstance = new ProcessInstance($bpmnEngine);
		$processInstance->merge($processDto);
		$processInstance->xmlAdapter->setProcessDefinitionXml($processInstance->process_definition_xml);
		return $processInstance;
	}
	static function buildByProcessDefinition($bpmnEngine, $processDefinition, $name){
		$processInstance = new ProcessInstance($bpmnEngine);
		$processInstance->variables = new VariableMap();
		$processInstance->_id = $name.":".md5(''.time());
		$processInstance->type = "process_instance";
		$processInstance->process_definition_xml = $processDefinition->xml;
		$processInstance->xmlAdapter->setProcessDefinitionXml($processDefinition->xml);
		$processInstance->created_ts = time();
		return $processInstance;
	}
	function start(){
		$this->discoverTasks($this->getAttribute($this->xmlAdapter->findStartEventElement(), 'id'), null);
	}

	function __construct($bpmnEngine) {
		$this->engine = $bpmnEngine;
		$this->xmlAdapter = new XmlAdapter();
	}

	public function findElementById($elementId){
		return $this->xmlAdapter->findElementById($elementId);
	}
	
	public function findSequenceFlowElementsBySourceElementExcludeDefault($element, $defaultId){
		return $this->xmlAdapter->findSequenceFlowElementsBySourceElementExcludeDefault($element, $defaultId);
	}
	public function findSequenceFlowElementsBySourceElement($element){
		return $this->xmlAdapter->findSequenceFlowElementsBySourceElement($element);
	}
	public function findSequenceFlowElementsByTargetElement($element){
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
			if( (! $isExecuted) && $handler->createTaskInstance($this, $element)){
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
	public function getProcessInstanceId(){
		return $this->_id;
	}
	function getTaskResult($element){
		$task = $this->getTaskByRefId($this->getAttribute($element, 'id'));
		return $task->result;
	}
	function createUserTaskInstance($element){
		$this->seq_taskId++;
		$task = new Task();
		$task->type = "userTask";
		$task->_id = $this->seq_taskId;
		$task->ref_id = $this->getAttribute($element, 'id');
		$task->retries = 0;
		$task->createdTs = time();
		$this->tasks[] = $task;
		return $task->_id;
	}
	function createTaskInstance($element){
		$this->seq_taskId++;
		$task = new Task();
		$task->createdTs = time();
		$task->type = $this->getName($element);
		$task->_id = $this->seq_taskId;
		$task->ref_id = $this->getAttribute($element, 'id');
		$task->retries = 3;
		$this->tasks[] = $task;
		return $task->_id;
	}
	function createSubProcessInstance($element){
		$this->seq_taskId++;
		$processInstance = new ProcessInstance($this->engine);
		$processInstance->created_ts = time();
		$processInstance->type = $this->getName($element);
		$processInstance->variables = $this->variables;
		$processInstance->xmlAdapter->setProcessDefinitionXml($this->process_definition_xml);
		$processInstance->_id = $this->seq_taskId;
		$processInstance->ref_id = $this->getAttribute($element, 'id');
		$processInstance->retries = 1;
		$this->tasks[] = $processInstance;
		return $processInstance;
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
	private function getNextReadyUserTask(){
		foreach($this->tasks as $i => $task)
		if($task->type == "userTask" && !isSet($task->executedTs) && isSet($task->result))
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
		$task = $this->getNextReadyUserTask();
		if($task){
			$this->executeUserTaskByRefId($task->ref_id, $task->result);
			return true;
		}else{
			return false;
		}
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


?>
