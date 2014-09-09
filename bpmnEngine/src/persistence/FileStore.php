<?php

namespace persistence;

class FileStore implements ProcessStore{

	private $processDefinitions = '/tmp/process_definitions';
	private $processes = '/tmp/processes';

	function __construct($dir = null) {
		if(null===$dir){
			$dir = sys_get_temp_dir();
		}
		$this->processDefinitions = realpath($dir).'/process_definitions';
		if( ! file_exists($this->processDefinitions) ){
			mkdir($this->processDefinitions);
		}
		$this->processes = realpath($dir).'/processes';
		if( ! file_exists($this->processes) ){
			mkdir($this->processes);
		}
	}

	function importDefinition($process_definition_xml){
		$simpleXml = new \SimpleXMLElement($process_definition_xml);
		$simpleXml->registerXPathNamespace("bpmn", "http://www.omg.org/spec/BPMN/20100524/MODEL");

		foreach($simpleXml->process as $process) {
			$pdId = (String) $process->attributes()->id; // TODO use XmlAdapter
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
			
			$myFile = $this->processDefinitions.'/'.md5($pdId).".json";
			file_put_contents($myFile, json_encode( $processDefinition ));
		}
	}

	function loadProcessDefinition($pdId){
		$myFile = $this->processDefinitions.'/'.md5($pdId).".json";
		if( file_exists($myFile) ) {
			$dto = json_decode( file_get_contents($myFile) );
			$dbObject = new \dto\ProcessDefinition();
			$dbObject->merge($dto);
			return $dbObject;
		}
	}
	
	public function listProcessDefinitions(){
		$processDefinitions = array();
		$d = dir($this->processDefinitions);
		while (false !== ($myFile = $d->read())) {
			if($myFile == '..' || $myFile == '.') continue;
			$dto = json_decode( file_get_contents($this->processDefinitions.'/'.$myFile) );
			$dbObject = new \dto\ProcessDefinition();
			$dbObject->merge($dto);
			$processDefinitions[] = $dbObject;
		}
		$d->close();
		return $processDefinitions;
	}

	function storeProcess($process){
		$myFile = $this->processes.'/'.md5($process->getId()).".json";
		{ // optimistick locking: validate version
			if( ! isSet($process->_version) ) $process->_version = 1;
			if( file_exists($myFile) ) {
				// check version
				$dto = json_decode( file_get_contents($myFile) );
				if( ! isSet($dto->_version) ) $dto->_version = 1;
				$refVersion = $dto->_version;
				if($process->_version != $dto->_version)
					throw new \Exception("ProcessInstance-File $myFile wurde von einem anderen Process verändert.");
				$process->_version++;
			}
		}
		file_put_contents($myFile, json_encode( $process ));
	}

	function loadProcess($processId){
		$myFile = $this->processes.'/'.md5($processId).".json";
		if( file_exists($myFile) ) {
			$dto = json_decode( file_get_contents($myFile) );
			$dbObject = new \dto\Process();
			$dbObject->merge($dto);
			return $dbObject;
		}
	}
		
	public function listProcesses(){
		$processes = array();
		$d = dir($this->processes);
		while (false !== ($myFile = $d->read())) {
			if($myFile == '..' || $myFile == '.') continue;
			$dto = json_decode( file_get_contents($this->processes.'/'.$myFile) );
			$dbObject = new \dto\Process();
			$dbObject->merge($dto);
			$processes[] = $dbObject;
		}
		$d->close();
		return $processes;
	}

	function findNotExecutedProcessInstanceIds(){
		$d = dir($this->processes);
		while (false !== ($myFile = $d->read())) {
			$process = json_decode( file_get_contents($this->processes.'/'.$myFile) );
			if( ! isSet($process->executed_ts) && isSet($process->tasks) ){
				foreach($process->tasks as $task) {
					if( ! isSet($task->executedTs) && isSet($task->result) ){
						$d->close();
						return $process->getId();
					}
				}
			}
		}
		$d->close();
	}

	function findOpenUserTasks(){
		$openUserTasks = array();
		$d = dir($this->processes);
		while (false !== ($myFile = $d->read())) {
			$process = json_decode( file_get_contents($this->processes.'/'.$myFile) );
			if( ! isSet($process->executed_ts) && isSet($process->tasks)){
				foreach($process->tasks as $task) {
					if( ! isSet($task->executedTs)
					 && ! isSet($task->result)
					 && ($task->type == "userTask" || $task->type == "manualTask")
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
		$d->close();
		return $openUserTasks;
	}
}
