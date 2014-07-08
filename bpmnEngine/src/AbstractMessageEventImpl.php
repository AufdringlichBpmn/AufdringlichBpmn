<?php

abstract class AbstractMessageEventImpl {
	static $messageEventHandlerMap = array();
	static function registerMessageEventHandler($name, $handler){
		self::$messageEventHandlerMap[$name] = $handler;
	}
	abstract function sendMessage($processInstance, $element);
	abstract function receiveMessage($processInstance, $element);
}
