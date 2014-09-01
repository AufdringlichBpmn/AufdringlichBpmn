<?php

namespace elements;

abstract class AbstractItemDefinitionImpl {
	static function canHandleItem(\ProcessInstance $processInstance, $element){
		$classname = $processInstance->getAttribute($element, 'structureRef');
		return $classname == get_called_class();
	}

	protected $bpmnEngine, $process, $element;
	function init($bpmnEngine, $process, $element){
		$this->bpmnEngine = $bpmnEngine;
		$this->process = $process;
		$this->element = $element;
	}
	abstract function asHtml();
	abstract function asInput();
}
