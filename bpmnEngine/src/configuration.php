<?php

$CONFIG = (object) array(
	"elementHandlers" => array(
		\elements\StartEventHandler::class,
		\elements\IntermediaCatchEventHandler::class,
		\elements\IntermediaThrowEventHandler::class,
		\elements\EndEventHandler::class,
		\elements\ExclusiveGatewayHandler::class,
		\elements\ParallelGatewayHandler::class,
		\elements\CallActivityHandler::class,
		\elements\ScriptTaskHandler::class,
		\elements\ServiceTaskHandler::class,
		\elements\UserTaskHandler::class,
		\elements\SubProcessHandler::class
	),
	"taskImpls" => array(
	),
	"eventImpls" => array(
		\elements\TimerEventImpl::class
	),
);
 