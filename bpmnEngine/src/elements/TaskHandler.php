<?php

namespace elements;

class TaskHandler extends DefaultBpmnElementHandler {
	
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
	
	protected function evaluate($processInstance, $element){
		$globalTaskId = $processInstance->getAttribute($element, 'calledElement');
		$globalScriptTask = $processInstance->findElementById($globalTaskId);
		$script = $globalScriptTask->script;
		$context = $processInstance;
		return eval($script);
	}
	
}

class ScriptTaskHandler extends TaskHandler {
	
	protected function evaluate($processInstance, $element){
		$script = $element->script;
		$context = $processInstance;
		return eval($script);
	}
	
}

// TODO write Test
class SubProcessHandler extends DefaultBpmnElementHandler {
	
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
	
	protected function evaluate($processInstance, $element){
		$classname = $processInstance->getAttribute($element, 'implementation');
		if( ! class_exists($classname)) throw new Exception("Implementation nicht gefunden: ".$classname);
		$class = new \ReflectionClass( $classname);
		$serviceTaskImpl = $class->newInstance();
		$serviceTaskImpl->init($processInstance,$element);
		return $serviceTaskImpl->processServiceTask();
	}
	
}

class UserTaskHandler extends TaskHandler {

	function createTaskInstance($processInstance, $element){
		$task = new \dto\Task();
		$task->type = "userTask";
		$task->ref_id = $processInstance->getAttribute($element, 'id');
		$task->retries = 0;
		$task->createdTs = time();
		$taskId = $processInstance->addTask($task);
		try{
			$classname = $processInstance->getAttribute($element, 'implementation');
			if( ! class_exists($classname)) throw new \Exception("Implementation nicht gefunden: ".$classname);
			$class = new \ReflectionClass( $classname);
			$userTaskImpl = $class->newInstance();
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
