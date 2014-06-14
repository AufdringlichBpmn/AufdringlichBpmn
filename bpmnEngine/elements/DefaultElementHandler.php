<?php

require_once 'EventHandler.php';
BpmnEngine::registerBpmnElementHandler('startEvent', new StartEventHandler);
BpmnEngine::registerBpmnElementHandler('intermediateCatchEvent', new IntermediaCatchEventHandler);
BpmnEngine::registerBpmnElementHandler('endEvent', new EndEventHandler);

require_once 'GatewayHandler.php';
BpmnEngine::registerBpmnElementHandler('exclusiveGateway', new ExclusiveGatewayHandler);
BpmnEngine::registerBpmnElementHandler('parallelGateway', new ParallelGatewayHandler);

require_once 'TaskHandler.php';
BpmnEngine::registerBpmnElementHandler('callActivity', new CallActivityHandler);
BpmnEngine::registerBpmnElementHandler('scriptTask', new ScriptTaskHandler);
BpmnEngine::registerBpmnElementHandler('serviceTask', new ServiceTaskHandler);
BpmnEngine::registerBpmnElementHandler('userTask', new UserTaskHandler);
BpmnEngine::registerBpmnElementHandler('subProcess', new SubProcessHandler);

/**
 *	Default implementation of an BPMN-Element. 
 *
 */
class DefaultBpmnElementHandler{
	/**
	 * it will be called after discovering an element on the BPMN-Graph.
	 * A Task needs to override this implementation.
	 */
	function createTaskInstance($processInstance, $element){ 
		return false;
	}

	/**
	 * it will be called after discovering an element on the BPMN-Graph.
	 * A Task needs to override this implementation.
	 */
	function createEventInstance($processInstance, $element){
		return false;
	}

	/**
	 * This is the standard implementation of how to work the sequence flows.
	 * Tasks and Gateways need to override the implementation.
	 */
	function discoverTasks($processInstance, $value, $element){
		print_r("discover $value");
		$default =  $processInstance->getAttribute($element, "default");
		$isExclusivGateway = $processInstance->getName($element) == "exclusivGateway";
		$isExclusivGatewayCondition = $value."?" ==  $processInstance->getAttribute($element, "name");
		$useDefault = true;
		// find sequence flows and create following tasks
		foreach($processInstance->findSequenceFlowElementsBySourceElementExcludeDefault($element, $default) as $sequenceFlow){
			// check expressions
			if($condition = $processInstance->getAttribute($sequenceFlow, "name")){
				if($value != $condition){
					continue;
				}
			}
			$processInstance->discoverTasks( $processInstance->getAttribute($sequenceFlow, "targetRef"), $value);
			$useDefault = false;
		}
		if($useDefault && $default && $sequenceFlow = $processInstance->findElementById($default)) {
			$processInstance->discoverTasks( $processInstance->getAttribute($sequenceFlow, "targetRef"), $value);
		}
		return true;
	}
}

