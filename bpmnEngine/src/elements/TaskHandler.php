<?php

namespace elements;

abstract class TaskHandler extends DefaultBpmnElementHandler {

	protected function findTaskImpl($processInstance, $element){
		global $CONFIG;
		foreach($CONFIG->taskImpls as $impl){
			if($impl::canHandleTask($processInstance, $element)){
				return new $impl;
			}
		}
		$elementName = $processInstance->getAttribute($element, 'name');
		throw new \Exception("No Impl found for $elementName.");
	}
	
	function createTaskInstance($processInstance, $element){
		$task = new \dto\Task();
		$task->createdTs = time();
		$task->type = $processInstance->getName($element);
		$task->ref_id = $processInstance->getAttribute($element, 'id');
		$task->retries = 3;
		$processInstance->addTask($task);
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

class CallActivityHandler extends TaskHandler {
	static function canHandleElement($elementName){
		return "callActivity" == $elementName;
	}

	protected function evaluate($processInstance, $element){
		$globalTaskId = $processInstance->getAttribute($element, 'calledElement');
		$globalScriptTask = $processInstance->findElementById($globalTaskId);
		$script = $globalScriptTask->script;
		$context = $processInstance;
		return eval($script);
	}
}

class ScriptTaskHandler extends TaskHandler {
	static function canHandleElement($elementName){
		return "scriptTask" == $elementName;
	}
	
	protected function evaluate($processInstance, $element){
		$script = $element->script;
		$context = $processInstance;
		return eval($script);
	}
}

// TODO write Test
class SubProcessHandler extends DefaultBpmnElementHandler {
	static function canHandleElement($elementName){
		return "subProcess" == $elementName;
	}
	
	function processTaskInstance($processInstance, $element, $taskId){
		$subProcess = new \ProcessInstance($this->engine, $this->xmlAdapter);
		$subProcess->created_ts = time();
		$subProcess->type = $this->getName($element);
		$subProcess->variables = $this->variables;
		$subProcess->_id = $this->seq_taskId;
		$subProcess->ref_id = $this->getAttribute($element, 'id');
		$subProcess->retries = 1;
		$processInstance->addTask($subProcess);
		$subProcess->discoverTasks($subProcess->xmlAdapter->findStartEventElement($element), null);
		if(isSet($subProcess->executedTs)){
			$elementId = $processInstance->getAttribute($element, "id");
			$processInstance->discoverTasks($elementId, $subProcess->result, true);
		}
	}
}

class ServiceTaskHandler extends TaskHandler {
	static function canHandleElement($elementName){
		return "serviceTask" == $elementName;
	}
	
	protected function evaluate($processInstance, $element){
		$serviceTaskImpl = self::findTaskImpl($processInstance, $element);
		$serviceTaskImpl->init($processInstance,$element);
		return $serviceTaskImpl->processServiceTask();
	}
}

class UserTaskHandler extends TaskHandler {
	static function canHandleElement($elementName){
		return "userTask" == $elementName;
	}

	function createTaskInstance($processInstance, $element){
		$task = new \dto\Task();
		$task->type = "userTask";
		$task->ref_id = $processInstance->getAttribute($element, 'id');
		$task->retries = 0;
		$task->createdTs = time();
		$taskId = $processInstance->addTask($task);
		try{
			$userTaskImpl = self::findTaskImpl($processInstance, $element);
			$userTaskImpl->init($processInstance, $element);
			$userTaskImpl->preProcessUserTask();
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

class ManualTaskHandler extends TaskHandler {
	static function canHandleElement($elementName){
		return "manualTask" == $elementName;
	}

	function createTaskInstance($processInstance, $element){
		$task = new \dto\Task();
		$task->type = "manualTask";
		$task->ref_id = $processInstance->getAttribute($element, 'id');
		$task->retries = 0;
		$task->createdTs = time();
		$taskId = $processInstance->addTask($task);
		return 2;
	}
	
	function evaluate($processInstance, $element){
		return $processInstance->getTaskResult($element);
	}
}

class SendTaskHandler extends TaskHandler {
	static function canHandleElement($elementName){
		return "sendTask" == $elementName;
	}
	
	protected function evaluate($processInstance, $element){
		$serviceTaskImpl = self::findTaskImpl($processInstance, $element);
		$serviceTaskImpl->init($processInstance,$element);
		return $serviceTaskImpl->processServiceTask();
	}
}

class ReceiveTaskHandler extends TaskHandler {
	static function canHandleElement($elementName){
		return "receiveTask" == $elementName;
	}
	
	protected function evaluate($processInstance, $element){
		$serviceTaskImpl = self::findTaskImpl($processInstance, $element);
		$serviceTaskImpl->init($processInstance,$element);
		return $serviceTaskImpl->processServiceTask();
	}
}
