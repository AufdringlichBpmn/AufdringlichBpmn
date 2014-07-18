<?php

require_once ('elements/BpmnElementHandler.php');
require_once ('elements/DefaultBpmnElementHandler.php');

class BpmnEngine{
	private $storage;
	private $xmlAdapter;

	function __construct(\persistence\ProcessStore $storage) {
		$this->storage = $storage;
		$this->xmlAdapter = new XmlAdapter();
	}

	public function importDefinition($process_definition_xml) {
		$this->storage->importDefinition($process_definition_xml);
	}

	public function startProcess($processDefintionId, $variables) {
		$processDefinition = $this->storage->loadProcessDefinition($processDefintionId);
		$process = ProcessInstance::buildByProcessDefinition($this, $processDefinition);
		foreach($variables as $key => $value){
			$process->put($key,$value);
		}
		$process->start();
		$this->storage->storeProcess($process);
		return $process;
	}
	public function executeUserTaskByRefId($process, $refId, $value = null){
		$process->executeTaskByRefId($refId, $value);
		$this->storage->storeProcess($process);
	}
	public function executeManualTaskByRefId($process, $refId, $value = null){
		$process->executeTaskByRefId($refId, $value);
		$this->storage->storeProcess($process);
	}
	function loadProcess($processId){
		$processDto = $this->storage->loadProcess($processId);
		$processDefinition = $this->storage->loadProcessDefinition($processDto->processDefinitionId);
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
}
