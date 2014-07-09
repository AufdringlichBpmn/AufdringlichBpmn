<?php

$CONFIG = (object) array(
	"eventHandlerByElementName" => array(
		'startEvent' => \elements\StartEventHandler::class,
		'intermediateCatchEvent' => \elements\IntermediaCatchEventHandler::class,
		'intermediateThrowEvent' => \elements\IntermediaThrowEventHandler::class,
		'endEvent' => \elements\EndEventHandler::class,
		'exclusiveGateway' => \elements\ExclusiveGatewayHandler::class,
		'parallelGateway' => \elements\ParallelGatewayHandler::class,
		'callActivity' => \elements\CallActivityHandler::class,
		'scriptTask' => \elements\ScriptTaskHandler::class,
		'serviceTask' => \elements\ServiceTaskHandler::class,
		'userTask' => \elements\UserTaskHandler::class,
		'subProcess' => \elements\SubProcessHandler::class
	),
	"taskImpls" => array(),
	"eventImpls" => array(),	
);
 