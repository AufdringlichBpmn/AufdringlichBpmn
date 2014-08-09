<?php

namespace elements;

class NoneEventImpl extends AbstractEventImpl{

	static function canHandleEvent(\ProcessInstance $processInstance, $elementId){
		$element = $processInstance->findElementById($elementId);
		return !( false
			|| isset($element->timerEventDefinition)
			|| isset($element->conditionalEventDefinition)
			|| isset($element->messageEventDefinition)
			|| isset($element->signalEventDefinition)
			|| isset($element->attributes()->messageRef)
		);
	}
	
	function isEventOccured(\ProcessInstance $processInstance, $event){
		$event->result = "occured at " . (date("M d Y H:i:s"));
		return true;
	}
}