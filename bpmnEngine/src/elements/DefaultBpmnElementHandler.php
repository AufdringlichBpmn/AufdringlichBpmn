<?php

namespace elements;

/**
 *	Default implementation of an BPMN-Element. 
 *
 */
class DefaultBpmnElementHandler implements BpmnElementHandler{
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
