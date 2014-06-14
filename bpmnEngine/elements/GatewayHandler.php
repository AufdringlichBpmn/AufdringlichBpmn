<?php

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
	function discoverTasks($processInstance, $value, $element){
		if($processInstance->isJoin($element)){
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

	private function checkParallelGateReady($processInstance, $refId){
		$task = $processInstance->getTaskByRefId($refId);
		if($task) return isSet($task->executedTs);
		return false;
	}

	function discoverTasks($processInstance, $value, $element){
		if($processInstance->isJoin($element)){
			foreach($processInstance->findSequenceFlowElementsByTargetElement($element) as $sequenceFlow){
				$refId = $processInstance->getAttribute($sequenceFlow, 'sourceRef');
				if( ! $processInstance->checkParallelGateReady( $refId ))
					return false;
			}
		}
		return parent::discoverTasks($processInstance, $value, $element);
	}
}