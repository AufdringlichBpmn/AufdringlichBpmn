<?php

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

class IntermediaCatchEventHandler extends DefaultBpmnElementHandler {
	function createEventInstance($processInstance, $element){
		$event = new Event();
		$event->ref_id = $processInstance->getAttribute($element, 'id');
		$event->createdTs = time();
		$event->type = "intermediateCatchEvent";
		if(isset($element->messageEventDefinition)){
			$event->subtype = "message";
		}else{
			$event->subtype = "timer";
			$event->timeDuration = (string) $element->timerEventDefinition->timeDuration;
			$event->timeout = (new DateTime())->add(new DateInterval($event->timeDuration))->getTimestamp();
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
			$queue = msg_get_queue(1);
			$msgType = 1;
			$msg = '';
			$maxsize = 1000*1000;
			$hasMsg = msg_receive ($queue, $msgType, $msgType, $maxsize, $message, true, MSG_IPC_NOWAIT);
			if($hasMsg) {
				$event->result = "received at " . (date("M d Y H:i:s"));
			}
			return $hasMsg;
		}
		return false;
	}
}

class IntermediaThrowEventHandler extends DefaultBpmnElementHandler {
	function createEventInstance($processInstance, $element){
		$event = new Event();
		$event->ref_id = $processInstance->getAttribute($element, 'id');
		$event->createdTs = time();
		$event->type = "intermediateThrowEvent";
		if(isset($element->messageEventDefinition)){
			$queue = msg_get_queue(1);
			$msgRefId = $processInstance->getAttribute($element->messageEventDefinition, 'messageRef');
			$msgDefinition = $processInstance->findElementById($msgRefId);
			$variable = $processInstance->getAttribute($msgDefinition, 'name');
			$msgType = 1;
			$msg = ''+$variable+"\0";
			msg_send($queue, $msgType, $msg, true, false);
		}
		$processInstance->addEvent($event);
		return 2;
	}
	
	function isEventOccured($processInstance, $event){
		$event->result = "send at " . (date("M d Y H:i:s"));
		return true;
	}
}

