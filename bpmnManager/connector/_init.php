<?php

include __DIR__.'/../../bpmnEngine/build/bpmn.phar';
require_once(__DIR__.'/../../bpmnEngine/test/BpmnEngineTest_TaskImpls.php');

$store = new \persistence\FileStore();
$bpmnEngine = new BpmnEngine($store);

