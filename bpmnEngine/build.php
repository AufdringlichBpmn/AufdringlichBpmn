<?php

$srcRoot = "./src";
$buildRoot = "./build";

$phar = new Phar($buildRoot . "/bpmn.phar",
	FilesystemIterator::CURRENT_AS_FILEINFO |
	FilesystemIterator::KEY_AS_FILENAME, "bpmn.phar");
$phar ["init.php"] = file_get_contents($srcRoot."/init.php");

$phar ["AbstractMessageEventImpl.php"] = file_get_contents($srcRoot."/AbstractMessageEventImpl.php");
$phar ["AbstractServiceTaskImpl.php"] = file_get_contents($srcRoot."/AbstractServiceTaskImpl.php");
$phar ["AbstractTaskImpl.php"] = file_get_contents($srcRoot."/AbstractTaskImpl.php");
$phar ["AbstractUserTaskImpl.php"] = file_get_contents($srcRoot."/AbstractUserTaskImpl.php");

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


$phar ["XmlAdapter.php"] = file_get_contents($srcRoot."/XmlAdapter.php");
$phar ["elements/DefaultElementHandler.php"] = file_get_contents($srcRoot."/elements/DefaultElementHandler.php");
$phar ["elements/EventHandler.php"] = file_get_contents($srcRoot."/elements/EventHandler.php");
$phar ["elements/GatewayHandler.php"] = file_get_contents($srcRoot."/elements/GatewayHandler.php");
$phar ["elements/TaskHandler.php"] = file_get_contents($srcRoot."/elements/TaskHandler.php");

$phar->setStub($phar->createDefaultStub("init.php"));