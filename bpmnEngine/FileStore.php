<?php


class FileStore implements ProcessStore{

	private $processDefinitions = '/tmp/process_definitions';
	private $processes = '/tmp/processes';
	
	function importDefinition($process_definition_xml){
		$simpleXml = new SimpleXMLElement($process_definition_xml);
		$simpleXml->registerXPathNamespace("bpmn", "http://www.omg.org/spec/BPMN/20100524/MODEL");

		foreach($simpleXml->process as $process) {
			$pdId = $process->attributes()->id;
			$processDefinition = $this->loadProcessDefinition($pdId);
			if($processDefinition) {
				$processDefinition->xml = $simpleXml->asXML();
			} else {
				$processDefinition = new ProcessDefinition(array(
						'type' => "process_definition",
						'_id' => $pdId,
						'xml' => $simpleXml->asXML()
				));
			}
			
			$myFile = $this->processDefinitions.'/'.md5($pdId).".json";
			file_put_contents($myFile, json_encode( $processDefinition, JSON_PRETTY_PRINT ));
		}
	}

	function loadProcessDefinition($pdId){
		$myFile = $this->processDefinitions.'/'.md5($pdId).".json";
		if( file_exists($myFile) ) {
			$dto = json_decode( file_get_contents($myFile) );
			$dbObject = new ProcessDefinition();
			$dbObject->merge($dto);
			return $dbObject;
		}
	}
	
	function storeProcess($process){
		$myFile = $this->processes.'/'.md5($process->getId()).".json";
		file_put_contents($myFile, json_encode( $process, JSON_PRETTY_PRINT ));
	}
	
	function loadProcess($processId){
		$myFile = $this->processes.'/'.md5($processId).".json";
		if( file_exists($myFile) ) {
			$dto = json_decode( file_get_contents($myFile) );
			$dbObject = new Process();
			$dbObject->merge($dto);
			return $dbObject;
		}
	}
		
	function findNotExecutedProcessInstanceIds(){
		$d = dir($this->processes);
		while (false !== ($myFile = $d->read())) {
			$process = json_decode( file_get_contents($this->processes.'/'.$myFile) );
			if( ! $process->executed_ts){
				foreach($process->tasks as $task) {
					if( ! $task->executedTs && $task->result){
						$d->close();
						return $process->getId();
					}
				}
			}
		}
		$d->close();
	}

}
