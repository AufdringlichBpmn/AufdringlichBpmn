<?php

namespace elements;

abstract class AbstractEventHandler extends DefaultBpmnElementHandler {
	function createEventInstance(\ProcessInstance $processInstance, $element){
		$event = new \dto\Event();
		$event->ref_id = $processInstance->getAttribute($element, 'id');
		$event->createdTs = time();
		$event->type = $processInstance->getName($element);
		$processInstance->addEvent($event);
		return 2;
	}
	
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

/**
 *	Start-Element.
 * E.g.
 *	<startEvent id="_2" isInterrupting="true" name="Start Event" parallelMultiple="false">
 *		<outgoing>_7</outgoing>
 *	</startEvent>
 */
class StartEventHandler extends AbstractEventHandler{
	function isEventOccured(\ProcessInstance $processInstance, $event){
		$handler = $this->findEventImpl($processInstance, $event->ref_id);
		if($handler->isEventOccured($processInstance, $event)){
			$event->result = "started at " . (date("M d Y H:i:s"));
			return true;
		}
	}
}

class EndEventHandler extends AbstractEventHandler{
	function createEventInstance(\ProcessInstance $processInstance, $element){
		// create Event
		$event = new \dto\Event();
		$event->ref_id = $processInstance->getAttribute($element, 'id');
		$event->createdTs = time();
		$event->type = $processInstance->getName($element);
	
		// is occured
		$event->result = "ended at " . (date("M d Y H:i:s"));
		$event->executedTs = time();
		$processInstance->addEvent($event);

		// finish underlying process
		$result = $processInstance->getAttribute($element, 'name');
		$processInstance->markProcessInstanceExecuted($result);		
		return true;
	}	
}

class BoundaryEventHandler extends AbstractEventHandler {
	// TODO implmenting isInterrupting -> cancel ref Task
	
	function isEventOccured(\ProcessInstance $processInstance, $event){
		$handler = $this->findEventImpl($processInstance, $event->ref_id);
		if($handler->isEventOccured($processInstance, $event)){
			$event->result = "catched at " . (date("M d Y H:i:s"));
			return true;
		}
	}
}

class IntermediateCatchEventHandler extends AbstractEventHandler {
	
	function isEventOccured(\ProcessInstance $processInstance, $event){
		$handler = $this->findEventImpl($processInstance, $event->ref_id);
		if($handler->isEventOccured($processInstance, $event)){
			$event->result = "catched at " . (date("M d Y H:i:s"));
			return true;
		}
	}
}

class IntermediateThrowEventHandler extends AbstractEventHandler {
	
	function isEventOccured(\ProcessInstance $processInstance, $event){
		$handler = $this->findEventImpl($processInstance, $event->ref_id);
		if($handler->isEventOccured($processInstance, $event)){
			$event->result = "thrown at " . (date("M d Y H:i:s"));
			return true;
		}
		return false;
	}
}
