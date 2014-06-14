<?php

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