<?php

$srcRoot = ".";
$buildRoot = "./build";

$phar = new Phar($buildRoot . "/bpmn.phar",
	FilesystemIterator::CURRENT_AS_FILEINFO |
	FilesystemIterator::KEY_AS_FILENAME, "bpmn.phar");
$phar ["BpmnEngine.php"] = file_get_contents($srcRoot."/BpmnEngine.php");
$phar ["XmlAdapter.php"] = file_get_contents($srcRoot."/XmlAdapter.php");
$phar ["CouchDbStore.php"] = file_get_contents($srcRoot."/CouchDbStore.php");
$phar ["InMemoryStore.php"] = file_get_contents($srcRoot."/InMemoryStore.php");
$phar ["FileStore.php"] = file_get_contents($srcRoot."/FileStore.php");
$phar ["elements/DefaultElementHandler.php"] = file_get_contents($srcRoot."/elements/DefaultElementHandler.php");
$phar ["elements/EventHandler.php"] = file_get_contents($srcRoot."/elements/EventHandler.php");
$phar ["elements/GatewayHandler.php"] = file_get_contents($srcRoot."/elements/GatewayHandler.php");
$phar ["elements/TaskHandler.php"] = file_get_contents($srcRoot."/elements/TaskHandler.php");

$phar->setStub($phar->createDefaultStub("BpmnEngine.php"));