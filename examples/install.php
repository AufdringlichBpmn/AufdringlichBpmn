<?php

require_once(__DIR__.'/../bpmnEngine/build/bpmn.phar');

$dbAdapter = new \persistence\FileStore();

$bpmnEngine = new BpmnEngine($dbAdapter);
$bpmnEngine->importDefinition(file_get_contents(__DIR__.'/VertragKuendigen.bpmn'));
