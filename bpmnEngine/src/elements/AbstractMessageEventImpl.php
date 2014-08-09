<?php

namespace elements;

abstract class AbstractMessageEventImpl extends AbstractEventImpl {
	static function canHandleEvent(
		\ProcessInstance $processInstance, $elementId){
		$element = $processInstance->findElementById($elementId);
		if(isset($element->messageEventDefinition)) {
			// via Event-Element
			$msgRefId = $processInstance->getAttribute($element->messageEventDefinition, 'messageRef');
			$msgDefinition = $processInstance->findElementById($msgRefId);
			$messageName = $processInstance->getAttribute($msgDefinition, 'name');
			return $messageName == get_called_class();
		}
		$msgRefId = $processInstance->getAttribute($element, 'messageRef');
		if($msgRefId){
			// via Task-Element
			$msgDefinition = $processInstance->findElementById($msgRefId);
			$messageName = $processInstance->getAttribute($msgDefinition, 'name');
			return $messageName == get_called_class();
		}
		return false;
	}

	function isEventOccured(\ProcessInstance $processInstance, $event){
		if($event->type == "intermediateThrowEvent") {
			return $this->sendMessage($processInstance, $event);
		}else if($event->type == "intermediateCatchEvent") {
			return $this->receiveMessage($processInstance, $event);
		}else if($event->type == "startEvent") {
			return $this->receiveMessage($processInstance, $event);
		}else{
			throw new \Exception("Type nicht erwartet: ".$event->type);
		}
	}
	abstract function sendMessage(\ProcessInstance $processInstance, $event);
	abstract function receiveMessage(\ProcessInstance $processInstance, $event);
}
