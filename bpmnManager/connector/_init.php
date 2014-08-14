<?php

include __DIR__.'/../../bpmnEngine/build/bpmn.phar';

chdir('..');

$store = new \persistence\FileStore();
$bpmnEngine = new BpmnEngine($store);

