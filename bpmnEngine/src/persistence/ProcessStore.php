<?php

namespace persistence;

interface ProcessStore {
	function importDefinition($simplexml);
	function loadProcessDefinition($name);
	function listProcessDefinitions();
	function storeProcess($process);
	function loadProcess($processId);
	function listProcesses();
	function findNotExecutedProcessInstanceIds();
	function findOpenUserTasks();
}

