<?php

$CONFIG = (object) array(
	"elementHandlers" => array(
		\elements\StartEventHandler::class,
		\elements\BoundaryEventHandler::class,
		\elements\IntermediateCatchEventHandler::class,
		\elements\IntermediateThrowEventHandler::class,
		\elements\EndEventHandler::class,
		\elements\ExclusiveGatewayHandler::class,
		\elements\ParallelGatewayHandler::class,
		\elements\CallActivityHandler::class,
		\elements\ScriptTaskHandler::class,
		\elements\ServiceTaskHandler::class,
		\elements\SendTaskHandler::class,
		\elements\ReceiveTaskHandler::class,
		\elements\UserTaskHandler::class,
		\elements\ManualTaskHandler::class,
		\elements\SubProcessHandler::class,
	),
	"taskImpls" => array(
	),
	"eventImpls" => array(
		\elements\TimerEventImpl::class,
		\elements\NoneEventImpl::class,
	),
);
 