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
$phar ["BpmnEngine.php"] = file_get_contents($srcRoot."/BpmnEngine.php");
$phar ["CouchDbStore.php"] = file_get_contents($srcRoot."/CouchDbStore.php");
$phar ["DbObject.php"] = file_get_contents($srcRoot."/DbObject.php");
$phar ["Event.php"] = file_get_contents($srcRoot."/Event.php");
$phar ["InMemoryStore.php"] = file_get_contents($srcRoot."/InMemoryStore.php");
$phar ["FileStore.php"] = file_get_contents($srcRoot."/FileStore.php");
$phar ["Process.php"] = file_get_contents($srcRoot."/Process.php");
$phar ["ProcessDefinition.php"] = file_get_contents($srcRoot."/ProcessDefinition.php");
$phar ["ProcessInstance.php"] = file_get_contents($srcRoot."/ProcessInstance.php");
$phar ["ProcessStore.php"] = file_get_contents($srcRoot."/ProcessStore.php");
$phar ["Task.php"] = file_get_contents($srcRoot."/Task.php");
$phar ["VariableMap.php"] = file_get_contents($srcRoot."/VariableMap.php");
$phar ["XmlAdapter.php"] = file_get_contents($srcRoot."/XmlAdapter.php");
$phar ["elements/DefaultElementHandler.php"] = file_get_contents($srcRoot."/elements/DefaultElementHandler.php");
$phar ["elements/EventHandler.php"] = file_get_contents($srcRoot."/elements/EventHandler.php");
$phar ["elements/GatewayHandler.php"] = file_get_contents($srcRoot."/elements/GatewayHandler.php");
$phar ["elements/TaskHandler.php"] = file_get_contents($srcRoot."/elements/TaskHandler.php");

$phar->setStub($phar->createDefaultStub("init.php"));