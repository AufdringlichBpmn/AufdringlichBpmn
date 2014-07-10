<?php

class CheckVariableA extends AbstractServiceTaskImpl{
	function processServiceTask(){
		return  $this->process->get( "a");
	}
}
// Tasks
class ServiceTaskImpl extends AbstractServiceTaskImpl{
	function processServiceTask(){
		return "success";
	}
}

class UserTaskImpl extends AbstractUserTaskImpl{
	static $testProcessInstanceId;
	function preProcessUserTask(){
		self::$testProcessInstanceId=$this->process->getId();
	}
}

// Gateways
class Eins extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 1");
		return 1;
	}
}
class Zwei extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 2");
		return 2;
	}
}
class Drei extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 3");
		return 3;
	}
}
class Vier extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 4");
		return 4;
	}
}
class Funf extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 5");
		return 5;
	}
}
class Sechs extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 6");
		return 6;
	}
}
class Sieben extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 7");
		return 7;
	}
}
class Acht extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 8");
		return 8;
	}
}
class Neun extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 9");
		return 9;
	}
}
class CheckResult extends AbstractServiceTaskImpl{
	function processServiceTask(){
		return $this->process->get( "visits");
	}
}

// EVENTS
class MessageSendingImpl extends AbstractMessageEventImpl{
	static function canHandleEvent(
		\ProcessInstance $processInstance, $elementId){
		$element = $processInstance->findElementById($elementId);
		if(!isset($element->messageEventDefinition)) 
			return false;
		$msgRefId = $processInstance->getAttribute($element->messageEventDefinition, 'messageRef');
		$msgDefinition = $processInstance->findElementById($msgRefId);
		$messageName = $processInstance->getAttribute($msgDefinition, 'name');
		return $messageName == get_called_class();
	}

	private $msgType;
	private $maxsize;
	private $queue;

	function __construct(){
		$this->msgType = 1;
		$this->maxsize = 1000;
		$this->queue = msg_get_queue(1);
	}
	function sendMessage(\ProcessInstance $processInstance, $event){
		$msg = "Hallo Welt\0";
		return msg_send($this->queue, $this->msgType, $msg, true, false);
	}
	function receiveMessage(\ProcessInstance $processInstance, $event){
		$message = '';
		$hasMsg = msg_receive ($this->queue, $this->msgType, $this->msgType,
			$this->maxsize, $message, true, MSG_IPC_NOWAIT);
		if($hasMsg) {
			$event->result = $message;
		}
		return $hasMsg;
	}
}
