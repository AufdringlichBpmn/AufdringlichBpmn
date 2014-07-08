<?php

abstract class AbstractTaskImpl{
	protected $process, $element;
	function init($process, $element){
		$this->process = $process;
		$this->element = $element;
	}
}
