<?php

namespace elements;

abstract class AbstractTaskImpl{
	static function canHandleTask($processInstance, $element){
		$classname = $processInstance->getAttribute($element, 'implementation');
		return $classname == get_called_class();
	}

	protected $process, $element;
	function init($process, $element){
		$this->process = $process;
		$this->element = $element;
	}
}
