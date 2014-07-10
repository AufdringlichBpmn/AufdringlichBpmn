<?php

namespace elements;

/**
 *	Handels an exclusive gateway.
 * E.g.
    <exclusiveGateway gatewayDirection="Unspecified" id="_13" name="2?">
      <incoming>_14</incoming>
      <outgoing>_15</outgoing>
      <outgoing>_18</outgoing>
    </exclusiveGateway>
 */
class ExclusiveGatewayHandler extends DefaultBpmnElementHandler{
	static function canHandleElement($elementName){
		return "exclusiveGateway" == $elementName;
	}
	function discoverTasks($processInstance, $value, $element){
		if($this->isJoin($processInstance, $element)){
			return parent::discoverTasks($processInstance, $value, $element);
		}else{
			$xvalue = ($value."?" == $processInstance->getAttribute($element, 'name')) ? "yes" : "no";
			// find sequence flows and create following tasks
			foreach($processInstance->findSequenceFlowElementsBySourceElement($element) as $sequenceFlow){
				// check expressions
				if($xvalue == $processInstance->getAttribute($sequenceFlow, 'name')){
					$processInstance->discoverTasks($processInstance->getAttribute($sequenceFlow, 'targetRef'), $value);
				}
			}
		}
		return true;
	}
}

class ParallelGatewayHandler extends DefaultBpmnElementHandler{
	static function canHandleElement($elementName){
		return "parallelGateway" == $elementName;
	}

	private function checkParallelGateReady($processInstance, $refId){
		$taskOrEvent = $processInstance->getTaskByRefId($refId);
		if( ! $taskOrEvent) $taskOrEvent = $processInstance->getEventByRefId($refId);
		if($taskOrEvent) return isSet($taskOrEvent->executedTs);
		return false;
	}

	function discoverTasks($processInstance, $value, $element){
		if($this->isJoin($processInstance, $element)){
			foreach($processInstance->findSequenceFlowElementsByTargetElement($element) as $sequenceFlow){
				$refId = $processInstance->getAttribute($sequenceFlow, 'sourceRef');
				if( ! $this->checkParallelGateReady($processInstance, $refId ))
					return false;
			}
		}
		return parent::discoverTasks($processInstance, $value, $element);
	}
}