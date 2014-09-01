<?php

namespace elements;

abstract class TaskHandler extends DefaultBpmnElementHandler {

	protected function findTaskImpl(\ProcessInstance $processInstance, $element){
		foreach ( get_declared_classes() as $c ) {
			$class = new \ReflectionClass($c);
			if ( $class->isSubclassOf('\elements\AbstractTaskImpl') && $class->IsInstantiable()) {
				$impl = $c;
				if($impl::canHandleTask($processInstance, $element)){
					return new $impl;
				}
			}
		}
		$elementName = $processInstance->getAttribute($element, 'name');
		throw new \Exception("No Impl found for $elementName.");
	}
	
	function createTaskInstance(\ProcessInstance $processInstance, $element){
		$task = new \dto\Task();
		$task->createdTs = time();
		$task->type = $processInstance->getName($element);
		$task->ref_id = $processInstance->getAttribute($element, 'id');
		$task->retries = 3;
		$processInstance->addTask($task);
		return 2;
	}
	
	abstract protected function evaluate(\ProcessInstance $processInstance, $element, $task);
	
	function processTaskInstance(\ProcessInstance $processInstance, $element, $taskId){
		$elementId = $processInstance->getAttribute($element, "id");
		$processInstance->decrementTaskRetries($taskId);
		$task = $processInstance->getTaskById($taskId);
		try{
			$result = $this->evaluate($processInstance, $element, $task);
			$processInstance->markTaskExecuted($taskId, $result);
			$processInstance->discoverTasks($elementId, $result, true);
		}catch(Exception $e){
			print_r($e->getMessage());
			$processInstance->setTaskExceptionMessage($taskId, $e->getMessage());
		}
	}
}

class CallActivityHandler extends TaskHandler {

	protected function evaluate(\ProcessInstance $processInstance, $element, $task){
		$globalTaskId = $processInstance->getAttribute($element, 'calledElement');
		$globalScriptTask = $processInstance->findElementById($globalTaskId);
		$script = $globalScriptTask->script;
		$context = $processInstance;
		return eval($script);
	}
}

class ScriptTaskHandler extends TaskHandler {
	
	protected function evaluate(\ProcessInstance $processInstance, $element, $task){
		$script = $element->script;
		$context = $processInstance;
		return eval($script);
	}
}

// TODO write Test
class SubProcessHandler extends DefaultBpmnElementHandler {
	
	function processTaskInstance(\ProcessInstance $processInstance, $element, $taskId){
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
	
	protected function evaluate(\ProcessInstance $processInstance, $element, $task){
		$serviceTaskImpl = self::findTaskImpl($processInstance, $element);
		$serviceTaskImpl->init($processInstance, $element);
		return $serviceTaskImpl->processServiceTask();
	}
}

class UserTaskHandler extends TaskHandler {

	function createTaskInstance(\ProcessInstance $processInstance, $element){
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
	
	function evaluate(\ProcessInstance $processInstance, $element, $task){
		return $processInstance->getTaskResult($element);
	}
}

class ManualTaskHandler extends TaskHandler {

	function createTaskInstance(\ProcessInstance $processInstance, $element){
		$task = new \dto\Task();
		$task->type = "manualTask";
		$task->ref_id = $processInstance->getAttribute($element, 'id');
		$task->retries = 0;
		$task->createdTs = time();
		$taskId = $processInstance->addTask($task);
		return 2;
	}
	
	function evaluate(\ProcessInstance $processInstance, $element, $task){
		return $processInstance->getTaskResult($element);
	}
}

abstract class AbstractEventTaskHandler extends TaskHandler {

	protected function findEventImpl(\ProcessInstance $processInstance, $elementId){
		foreach ( get_declared_classes() as $c ) {
			$class = new \ReflectionClass($c);
			if ( $class->isSubclassOf('\elements\AbstractEventImpl') && $class->IsInstantiable()) {
				$impl = $c;
				if($impl::canHandleEvent($processInstance, $elementId)){
					return new $impl;
				}
			}
		}
		throw new \Exception("No Impl found for ElementId=$elementId.");
	}
	protected function evaluate(\ProcessInstance $processInstance, $element, $task){
		throw new \Exception("Type nicht erwartet: ".$task->type);
	}
}

class SendTaskHandler extends AbstractEventTaskHandler {

	protected function evaluate(\ProcessInstance $processInstance, $element, $task){
		$elementId = $processInstance->getAttribute($element, 'id');
		$eventImpl = $this->findEventImpl($processInstance,  $elementId);
		return $eventImpl->sendMessage($processInstance, $task);
	}
	
}

class ReceiveTaskHandler extends AbstractEventTaskHandler {

	protected function evaluate(\ProcessInstance $processInstance, $element, $task){
		$elementId = $processInstance->getAttribute($element, 'id');
		$eventImpl = $this->findEventImpl($processInstance,  $elementId);
		if($eventImpl->receiveMessage($processInstance, $task)){
			return $task->result;
		}
		return false;
	}
	
}
