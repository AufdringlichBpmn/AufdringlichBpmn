<?php

$srcRoot = ".";
$buildRoot = "./build";

$phar = new Phar($buildRoot . "/bpmn.phar",
	FilesystemIterator::CURRENT_AS_FILEINFO |
	FilesystemIterator::KEY_AS_FILENAME, "bpmn.phar");
$phar ["BpmnEngine.php"] = file_get_contents($srcRoot."/BpmnEngine.php");

$phar->setStub($phar->createDefaultStub("BpmnEngine.php"));

