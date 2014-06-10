<?php

$srcRoot = ".";
$buildRoot = "./build";

$phar = new Phar($buildRoot . "/bpmn.phar",
	FilesystemIterator::CURRENT_AS_FILEINFO |
	FilesystemIterator::KEY_AS_FILENAME, "bpmn.phar");
$phar ["BpmnEngine.php"] = file_get_contents($srcRoot."/BpmnEngine.php");
$phar ["XmlAdapterTrait.php"] = file_get_contents($srcRoot."/XmlAdapterTrait.php");
$phar ["CouchDbStore.php"] = file_get_contents($srcRoot."/CouchDbStore.php");
$phar ["InMemoryStore.php"] = file_get_contents($srcRoot."/InMemoryStore.php");
$phar ["FileStore.php"] = file_get_contents($srcRoot."/FileStore.php");

$phar->setStub($phar->createDefaultStub("BpmnEngine.php"));