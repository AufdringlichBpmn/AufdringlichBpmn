<?php

$srcRoot = "./src";
$buildRoot = "./build";

unlink($buildRoot . "/bpmn.phar");
$phar = new Phar($buildRoot . "/bpmn.phar",
	FilesystemIterator::CURRENT_AS_FILEINFO |
	FilesystemIterator::KEY_AS_FILENAME, "bpmn.phar");
$phar ["init.php"] = file_get_contents($srcRoot."/init.php");

$phar ["elements/AbstractEventImpl.php"] = file_get_contents($srcRoot."/elements/AbstractEventImpl.php");
$phar ["elements/AbstractMessageEventImpl.php"] = file_get_contents($srcRoot."/elements/AbstractMessageEventImpl.php");
$phar ["elements/TimerEventImpl.php"] = file_get_contents($srcRoot."/elements/TimerEventImpl.php");
$phar ["elements/NoneEventImpl.php"] = file_get_contents($srcRoot."/elements/NoneEventImpl.php");
$phar ["elements/AbstractServiceTaskImpl.php"] = file_get_contents($srcRoot."/elements/AbstractServiceTaskImpl.php");
$phar ["elements/AbstractTaskImpl.php"] = file_get_contents($srcRoot."/elements/AbstractTaskImpl.php");
$phar ["elements/AbstractUserTaskImpl.php"] = file_get_contents($srcRoot."/elements/AbstractUserTaskImpl.php");
$phar ["elements/AbstractItemDefinitionImpl.php"] = file_get_contents($srcRoot."/elements/AbstractItemDefinitionImpl.php");

$phar ["dto/DbObject.php"] = file_get_contents($srcRoot."/dto/DbObject.php");
$phar ["dto/Event.php"] = file_get_contents($srcRoot."/dto/Event.php");
$phar ["dto/Process.php"] = file_get_contents($srcRoot."/dto/Process.php");
$phar ["dto/ProcessDefinition.php"] = file_get_contents($srcRoot."/dto/ProcessDefinition.php");
$phar ["dto/Task.php"] = file_get_contents($srcRoot."/dto/Task.php");
$phar ["dto/VariableMap.php"] = file_get_contents($srcRoot."/dto/VariableMap.php");

$phar ["ProcessInstance.php"] = file_get_contents($srcRoot."/ProcessInstance.php");

$phar ["persistence/ProcessStore.php"] = file_get_contents($srcRoot."/persistence/ProcessStore.php");
$phar ["persistence/CouchDbStore.php"] = file_get_contents($srcRoot."/persistence/CouchDbStore.php");
$phar ["persistence/InMemoryStore.php"] = file_get_contents($srcRoot."/persistence/InMemoryStore.php");
$phar ["persistence/FileStore.php"] = file_get_contents($srcRoot."/persistence/FileStore.php");

$phar ["BpmnEngine.php"] = file_get_contents($srcRoot."/BpmnEngine.php");
$phar ["configuration.php"] = file_get_contents($srcRoot."/configuration.php");


$phar ["XmlAdapter.php"] = file_get_contents($srcRoot."/XmlAdapter.php");
$phar ["elements/BpmnElementHandler.php"] = file_get_contents($srcRoot."/elements/BpmnElementHandler.php");
$phar ["elements/DefaultBpmnElementHandler.php"] = file_get_contents($srcRoot."/elements/DefaultBpmnElementHandler.php");
$phar ["elements/EventHandler.php"] = file_get_contents($srcRoot."/elements/EventHandler.php");
$phar ["elements/GatewayHandler.php"] = file_get_contents($srcRoot."/elements/GatewayHandler.php");
$phar ["elements/TaskHandler.php"] = file_get_contents($srcRoot."/elements/TaskHandler.php");

$phar->setStub($phar->createDefaultStub("init.php"));