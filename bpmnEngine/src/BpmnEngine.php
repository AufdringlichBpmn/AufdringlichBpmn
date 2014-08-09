<?php

require_once ('elements/BpmnElementHandler.php');
require_once ('elements/DefaultBpmnElementHandler.php');

class BpmnEngine{
	private $storage;

	function __construct(\persistence\ProcessStore $storage) {
		$this->storage = $storage;
	}

	public function importDefinition($process_definition_xml) {
		$this->storage->importDefinition($process_definition_xml);
	}

	public function startProcess($processDefinitionId, $variables) {
		$processDefinition = $this->storage->loadProcessDefinition($processDefinitionId);
		$process = ProcessInstance::buildByProcessDefinition($this, $processDefinition);
		foreach($variables as $key => $value){
			$process->put($key,$value);
		}
		$process->start();
		// process all evaluated events and tasks 
		while($process->processNextUserTask() || $process->processNextEvent());
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
		 // process all evaluated events and tasks 
		while($process->processNextUserTask() || $process->processNextEvent());
		$this->storage->storeProcess($process);
		return $process;
	}

	function findNotExecutedProcessInstanceIds(){
		return $this->storage->findNotExecutedProcessInstanceIds();
	}
	
	public function startProcessByEvent(){
		global $CONFIG;
		foreach($this->storage->listProcessDefinitions() as $processDefinition){
			
			$xmlAdapter = new XmlAdapter();
			$xmlAdapter->setProcessDefinitionXml($processDefinition->xml);
			$startEventElement = $xmlAdapter->findStartEventElement();
			$elementId = $xmlAdapter->getAttribute($startEventElement, 'id');
			
			$process = ProcessInstance::buildByProcessDefinition($this, $processDefinition);
			foreach($CONFIG->eventImpls as $impl){
				if($impl::canHandleEvent($process, $elementId)){
					if($impl != \elements\NoneEventImpl::class ){
						$process->start();
						// process all evaluated events and tasks 
						while($process->processNextUserTask() || $process->processNextEvent());
						// store process if something happened
						if(isSet($process->events[0]->executedTs)){
							$this->storage->storeProcess($process);
							return $process;
						}
					}
					break;
				}
			}
		}
	}
}
