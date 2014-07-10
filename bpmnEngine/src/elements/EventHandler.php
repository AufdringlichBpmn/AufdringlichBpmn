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
	static function canHandleElement($elementName){
		return "startEvent" == $elementName;
	}
}

class EndEventHandler extends DefaultBpmnElementHandler{
	static function canHandleElement($elementName){
		return "endEvent" == $elementName;
	}
	function discoverTasks($processInstance, $value, $element){
		$result = $processInstance->getAttribute($element, 'name');
		$processInstance->markProcessInstanceExecuted($result);
		return false;
	}
}

class IntermediaCatchEventHandler extends DefaultBpmnElementHandler {
	static function canHandleElement($elementName){
		return "intermediateCatchEvent" == $elementName;
	}
	function createEventInstance($processInstance, $element){
		$event = new \dto\Event();
		$event->ref_id = $processInstance->getAttribute($element, 'id');
		$event->createdTs = time();
		$event->type = "intermediateCatchEvent";
		if(isset($element->messageEventDefinition)){
			$event->subtype = "message";
			$msgRefId = $processInstance->getAttribute($element->messageEventDefinition, 'messageRef');
			$msgDefinition = $processInstance->findElementById($msgRefId);
			$event->handler = $processInstance->getAttribute($msgDefinition, 'name');
		}else{
			$event->subtype = "timer";
			$event->timeDuration = (string) $element->timerEventDefinition->timeDuration;
			$event->timeout = time(); // todo php5.3
//			$event->timeout = (new DateTime)->add(new DateInterval($event->timeDuration))->getTimestamp();
		}
		$processInstance->addEvent($event);
		return 2;
	}
	
	function isEventOccured($processInstance, $event){
		$event->lastCheck = time();
		if($event->subtype == "timer"){
			if( time() > $event->timeout) {
				$event->result = "occured at " . (date("M d Y H:i:s"));
				return true;
			}
		} else if($event->subtype == "message"){
			$handler = \AbstractMessageEventImpl::$messageEventHandlerMap[$event->handler];
			return $handler->receiveMessage($processInstance, $event);
		}
		return false;
	}
}

class IntermediaThrowEventHandler extends DefaultBpmnElementHandler {
	static function canHandleElement($elementName){
		return "intermediateThrowEvent" == $elementName;
	}
	function createEventInstance($processInstance, $element){
		$event = new \dto\Event();
		$event->ref_id = $processInstance->getAttribute($element, 'id');
		$event->createdTs = time();
		$event->type = "intermediateThrowEvent";
		if(isset($element->messageEventDefinition)){
			$event->subtype = "message";
			$msgRefId = $processInstance->getAttribute($element->messageEventDefinition, 'messageRef');
			$msgDefinition = $processInstance->findElementById($msgRefId);
			$event->handler = $processInstance->getAttribute($msgDefinition, 'name');
		}
		$processInstance->addEvent($event);
		return 2;
	}
	
	function isEventOccured($processInstance, $event){
		if($event->subtype == "message"){
			$handler = \AbstractMessageEventImpl::$messageEventHandlerMap[$event->handler];
			$handler->sendMessage($processInstance, $event);
			$event->result = "send at " . (date("M d Y H:i:s"));
			return true;
		}
		return false;
	}
}
