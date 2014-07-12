<?php

namespace elements;

class TimerEventImpl extends AbstractEventImpl{

	static function canHandleEvent(
		\ProcessInstance $processInstance, $elementId){
		$element = $processInstance->findElementById($elementId);
		return (isset($element->timerEventDefinition));
	}
	
	function isEventOccured(\ProcessInstance $processInstance, $event){
		if(!isSet($event->timeout)){
			$element = $processInstance->findElementById($event->ref_id);
			$event->timeDuration = (string) $element->timerEventDefinition->timeDuration;
			$event->timeout = (new \DateTime)->add(new \DateInterval($event->timeDuration))->getTimestamp();
		}
		if( time() > $event->timeout) {
			$event->result = "occured at " . (date("M d Y H:i:s"));
			return true;
		}
		return false;
	}
}