<?php
require_once ('elements/DefaultElementHandler.php');

class BpmnEngine{
	private $storage;
	private $xmlAdapter;
	private $name;

	function __construct(ProcessStore $storage, $name) {
		$this->storage = $storage;
		$this->name = $name;
		$this->xmlAdapter = new XmlAdapter();
	}

	public function importDefinition($process_definition_xml) {
		$this->storage->importDefinition($process_definition_xml);
	}

	public function startProcess($variables) {
		$processDefinition = $this->storage->loadProcessDefinition($this->name);
		$process = ProcessInstance::buildByProcessDefinition($this, $processDefinition, $this->name);
		foreach($variables as $key => $value){
			$process->put($key,$value);
		}
		$process->start();
		$this->storage->storeProcess($process);
		return $process;
	}
	public function executeUserTaskByRefId($process, $refId, $value = null){
		$process->executeUserTaskByRefId($refId, $value);
		$this->storage->storeProcess($process);
	}
	function loadProcess($processId){
		$processDefinition = $this->storage->loadProcessDefinition($this->name);
		$processDto = $this->storage->loadProcess($processId);
		$process = ProcessInstance::buildByDto($this, $processDefinition->xml, $processDto);
		return $process;
	}
	function continueProcess($processId){
		$process = $this->loadProcess($processId);
		while($process->processNextUserTask()); // all evaluated usertasks
		while($process->processNextEvent()); // all evaluated events 
		$this->storage->storeProcess($process);
		return $process;
	}

	function findNotExecutedProcessInstanceIds(){
		// TODO filter nach ProcessDefinition
		return $this->storage->findNotExecutedProcessInstanceIds();
	}

	private static $bpmnElemenHandlerMap = array();
	static function registerBpmnElementHandler($name, $handler){
		self::$bpmnElemenHandlerMap[$name] = $handler;
	}
	function getBpmnElementHandler($name){
		print "\n".$name.":\n";
		return self::$bpmnElemenHandlerMap[$name];
	}
}
