<?php

require_once 'EventHandler.php';
BpmnEngine::registerBpmnElementHandler('startEvent', new StartEventHandler);
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
 *	Default implementation of an BPMN-Element. It is used for Start-Element.
 * E.g.
 *	<startEvent id="_2" isInterrupting="true" name="Start Event" parallelMultiple="false">
 *		<outgoing>_7</outgoing>
 *	</startEvent>
 */
class DefaultBpmnElementHandler{
	function createTaskInstance($processInstance, $element){
		return false;
	}
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

