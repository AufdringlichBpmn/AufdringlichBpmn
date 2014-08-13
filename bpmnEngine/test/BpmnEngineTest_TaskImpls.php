<?php

class CheckVariableA extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		return  $this->process->get( "a");
	}
}
// Tasks
class ServiceTaskImpl extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		return "success";
	}
}

class UserTaskImpl extends \elements\AbstractUserTaskImpl{
	static $testProcessInstanceId;
	function preProcessUserTask(){
		self::$testProcessInstanceId=$this->process->getId();
	}
}

class SendTaskImpl extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		return "success";
	}
}

class ReceiveTaskImpl extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		return "success";
	}
}

// Gateways
class Eins extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 1");
		return 1;
	}
}
class Zwei extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 2");
		return 2;
	}
}
class Drei extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 3");
		return 3;
	}
}
class Vier extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 4");
		return 4;
	}
}
class Funf extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 5");
		return 5;
	}
}
class Sechs extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 6");
		return 6;
	}
}
class Sieben extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 7");
		return 7;
	}
}
class Acht extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 8");
		return 8;
	}
}
class Neun extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 9");
		return 9;
	}
}
class CheckResult extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		return $this->process->get( "visits");
	}
}

// EVENTS
class MessageSendingImpl extends \elements\AbstractMessageEventImpl{

	private $msgType;
	private $maxsize;
	private $queue;

	function __construct(){
		$this->msgType = 1;
		$this->maxsize = 1000;
		$this->queue = msg_get_queue(1);
	}
	function sendMessage(\ProcessInstance $processInstance, $event){
		$msg = "success";
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

class DummyMessageEventImpl extends \elements\AbstractMessageEventImpl{
	public static $receiveMessageReturns = true;

	function sendMessage(\ProcessInstance $processInstance, $event){
		return false;
	}
	function receiveMessage(\ProcessInstance $processInstance, $event){
		return self::$receiveMessageReturns;
	}
}

global $CONFIG;
$CONFIG->taskImpls[] = CheckVariableA::class;
$CONFIG->taskImpls[] = ServiceTaskImpl::class;
$CONFIG->taskImpls[] = UserTaskImpl::class;
$CONFIG->taskImpls[] = SendTaskImpl::class;
$CONFIG->taskImpls[] = ReceiveTaskImpl::class;
$CONFIG->taskImpls[] = Eins::class;
$CONFIG->taskImpls[] = Zwei::class;
$CONFIG->taskImpls[] = Drei::class;
$CONFIG->taskImpls[] = Vier::class;
$CONFIG->taskImpls[] = Funf::class;
$CONFIG->taskImpls[] = Sechs::class;
$CONFIG->taskImpls[] = Sieben::class;
$CONFIG->taskImpls[] = Acht::class;
$CONFIG->taskImpls[] = Neun::class;
$CONFIG->taskImpls[] = CheckResult::class;
$CONFIG->eventImpls[] = MessageSendingImpl::class;

