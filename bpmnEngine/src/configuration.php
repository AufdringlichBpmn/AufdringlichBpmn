<?php

$CONFIG = (object) array(
	"eventHandlerByElementName" => array(
		'startEvent' => \elements\StartEventHandler::getClassName(),
		'intermediateCatchEvent' => \elements\IntermediaCatchEventHandler::getClassName(),
		'intermediateThrowEvent' => \elements\IntermediaThrowEventHandler::getClassName(),
		'endEvent' => \elements\EndEventHandler::getClassName(),
		'exclusiveGateway' => \elements\ExclusiveGatewayHandler::getClassName(),
		'parallelGateway' => \elements\ParallelGatewayHandler::getClassName(),
		'callActivity' => \elements\CallActivityHandler::getClassName(),
		'scriptTask' => \elements\ScriptTaskHandler::getClassName(),
		'serviceTask' => \elements\ServiceTaskHandler::getClassName(),
		'userTask' => \elements\UserTaskHandler::getClassName(),
		'subProcess' => \elements\SubProcessHandler::getClassName()
	),
	"taskImpls" => array(),
	"eventImpls" => array(),	
);
 