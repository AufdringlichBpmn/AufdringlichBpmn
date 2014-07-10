<?php

abstract class AbstractEventImpl {
	abstract static function canHandleEvent(
		\ProcessInstance $processInstance, $elementId);

	abstract function isEventOccured(
		\ProcessInstance $processInstance, $event);
}
