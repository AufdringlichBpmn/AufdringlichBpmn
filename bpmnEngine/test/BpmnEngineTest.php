<?php

class BpmnEngineTest extends PHPUnit_Framework_TestCase{
	private $dbAdapter;

	protected function setUp() {
		require_once('DbObject.php');
		require_once('Task.php');
		require_once('Event.php');
		require_once('ProcessDefinition.php');
		require_once('ProcessStore.php');
		require_once('FileStore.php');
		require_once('XmlAdapter.php');
		require_once('VariableMap.php');
		require_once('Process.php');
		require_once('ProcessInstance.php');
		require_once('BpmnEngine.php');
		require_once('AbstractMessageEventImpl.php');
		require_once('AbstractTaskImpl.php');
		require_once('AbstractServiceTaskImpl.php');
		require_once('AbstractUserTaskImpl.php');
		require_once('BpmnEngineTest_TaskImpls.php');

//		$this->dbAdapter = new InMemoryStore();

		// Use this for CouchDB		
// 		$options['host'] = "localhost";
// 		$options['port'] = 5984;
// 		$options['db'] = 'test';
//		$this->dbAdapter = new CouchDbStore($options);
//		$this->dbAdapter->updateDesignDocument();

		// Or use this for File Persistence
		$this->dbAdapter = new FileStore();
		
	}

	protected function tearDown(){
	}

	public function testGateways(){
		$bpmnEngine = new BpmnEngine($this->dbAdapter, "GATEWAY_TEST");
		$bpmnEngine->importDefinition(file_get_contents(__DIR__.'/GatewayTest.bpmn'));

		$valueMap = array("visits" => "start");
		$process = $bpmnEngine->startProcess($valueMap);
		$result = $process->getResult();
		$this->assertEquals("success", $result);
	}

	public function testTasks(){
		$bpmnEngine = new BpmnEngine($this->dbAdapter, "TASKS_TEST");
		$bpmnEngine->importDefinition(file_get_contents(__DIR__.'/TasksTest.bpmn'));

		$valueMap = array("visits" => "start");
		$process = $bpmnEngine->startProcess($valueMap);
		// null, wegen process wartet auf UserTask
		$this->assertEquals(null, $process->getResult());
		$processId = $process->getId();
		$this->assertEquals($processId, UserTaskImpl::$testProcessInstanceId);
		// test reload from DB
		$process = $bpmnEngine->loadProcess($processId);
		$this->assertEquals("start", $process->get("visits"));
		// UserTask als vollendet markieren, Process weiterlaufen lassen
		$bpmnEngine->executeUserTaskByRefId($process, "_19", "success");
		$this->assertEquals("all success", $process->getResult());
	}
	
	public function testEvents(){
		AbstractMessageEventImpl::registerMessageEventHandler('TestMessage', new MessageSendingImpl);
	
		$bpmnEngine = new BpmnEngine($this->dbAdapter, "EVENTS_TEST");
		$bpmnEngine->importDefinition(file_get_contents(__DIR__.'/EventTest.bpmn'));

		$valueMap = array("visits" => "start");
		$process = $bpmnEngine->startProcess($valueMap);
		
		print_r("Warte 2 Sekunden um TimeOut zu erzeugen.");
		sleep (2);
		$process = $bpmnEngine->continueProcess($process->getId());
		$result = $process->getResult();

		//	print_r(	$queue = msg_get_queue(1));
		//	msg_send($queue, 12, "Hello from PHP!\0", false);
		//	print_r(	msg_stat_queue($queue));

//		print_r($process);
		$this->assertEquals("End Event", $result);
	}
}


?>
