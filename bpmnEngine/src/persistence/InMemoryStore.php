<?php

namespace persistence;

class InMemoryStore implements ProcessStore{

	private $processDefinitions = array();
	private $processes = array();
	
	function importDefinition($process_definition_xml){
		$simpleXml = new \SimpleXMLElement($process_definition_xml);
		$simpleXml->registerXPathNamespace("bpmn", "http://www.omg.org/spec/BPMN/20100524/MODEL");

		foreach($simpleXml->process as $process) {
			$pdId = (String) $process->attributes()->id;
			$processDefinition = $this->loadProcessDefinition($pdId);
			if($processDefinition) {
				$processDefinition->xml = $simpleXml->asXML();
			} else {
				$processDefinition = new \dto\ProcessDefinition(array(
						'type' => "process_definition",
						'_id' => $pdId,
						'xml' => $simpleXml->asXML()
				));
			}
			$this->processDefinitions[] = $processDefinition;
		}
	}
	
	function loadProcessDefinition($id){
		foreach($this->processDefinitions as $processDefinition) {
			if($processDefinition->getId() == $id){
				return $processDefinition;
			}
		}
	}

	public function listProcessDefinitions(){
		return $this->processDefinitions;
	}
	
	public function findOpenUserTasks(){
		$openUserTasks = array();
		foreach($this->processes as $process) {
			if( ! $process->executed_ts){
				foreach($process->tasks as $task) {
					if( ! $task->executedTs
					 && ! $task->result
					 && $task->type == "userTask"
					){
						$openUserTasks[] = array(
							"taskId"=>$task->_id,
							"processId"=>$process->_id,
							"processDefinitionId" => explode(":", $process->_id, 2)[0]
						);
					}
				}
			}
		}
		return $openUserTasks;
	}

	function storeProcess($process){
		$this->processes[] = $process;
	}
	
	function loadProcess($processId){
		foreach($this->processes as $process) {
			if($process->getId() == $processId){
				return $process;
			}
		}
	}
	
	public function listProcesses(){
		return $this->processes;
	}
		
	function findNotExecutedProcessInstanceIds(){
		foreach($this->processes as $process) {
			if( ! $process->executed_ts){
				foreach($process->tasks as $task) {
					if( ! $task->executedTs && $task->result){
						return $process->getId();
					}
				}
			}
		}
	}

}
