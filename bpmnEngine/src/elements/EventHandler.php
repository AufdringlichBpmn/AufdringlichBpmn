<?php

namespace elements;

/**
 *	Start-Element.
 * E.g.
 *	<startEvent id="_2" isInterrupting="true" name="Start Event" parallelMultiple="false">
 *		<outgoing>_7</outgoing>
 *	</startEvent>
 */
class StartEventHandler extends DefaultBpmnElementHandler{
}

class EndEventHandler extends DefaultBpmnElementHandler{
	function discoverTasks($processInstance, $value, $element){
		$result = $processInstance->getAttribute($element, 'name');
		$processInstance->markProcessInstanceExecuted($result);
		return false;
	}
}

abstract class AbstractIntermediateEventHandler extends DefaultBpmnElementHandler {
	protected function findEventImpl(\ProcessInstance $processInstance, $elementId){
		global $CONFIG;
		foreach($CONFIG->eventImpls as $impl){
			if($impl::canHandleEvent($processInstance, $elementId)){
				return new $impl;
			}
		}
		throw new \Exception("No Impl found for ElementId=$elementId.");
	}
}

class IntermediateCatchEventHandler extends AbstractIntermediateEventHandler {
	function createEventInstance(\ProcessInstance $processInstance, $element){
		$event = new \dto\Event();
		$event->ref_id = $processInstance->getAttribute($element, 'id');
		$event->createdTs = time();
		$event->type = "intermediateCatchEvent";
		$processInstance->addEvent($event);
		return 2;
	}
	
	function isEventOccured(\ProcessInstance $processInstance, $event){
		$handler = $this->findEventImpl($processInstance, $event->ref_id);
		if($handler->isEventOccured($processInstance, $event)){
			$event->result = "catched at " . (date("M d Y H:i:s"));
			return true;
		}
	}
}

class IntermediateThrowEventHandler extends AbstractIntermediateEventHandler {
	function createEventInstance(\ProcessInstance $processInstance, $element){
		$event = new \dto\Event();
		$event->ref_id = $processInstance->getAttribute($element, 'id');
		$event->createdTs = time();
		$event->type = "intermediateThrowEvent";
		$processInstance->addEvent($event);
		return 2;
	}
	
	function isEventOccured(\ProcessInstance $processInstance, $event){
		$handler = $this->findEventImpl($processInstance, $event->ref_id);
		if($handler->isEventOccured($processInstance, $event)){
			$event->result = "thrown at " . (date("M d Y H:i:s"));
			return true;
		}
		return false;
	}
}
