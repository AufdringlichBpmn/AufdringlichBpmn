<?php

abstract class AbstractMessageEventImpl extends AbstractEventImpl {

	function isEventOccured(\ProcessInstance $processInstance, $event){
		if($event->type == "intermediateThrowEvent") {
			return $this->sendMessage($processInstance, $event);
		}else if($event->type == "intermediateCatchEvent") {
			return $this->receiveMessage($processInstance, $event);
		}else{
			throw new \Exception("Type nicht erwartet: ".$event->type);
		}
	}
	abstract function sendMessage(\ProcessInstance $processInstance, $event);
	abstract function receiveMessage(\ProcessInstance $processInstance, $event);
}
