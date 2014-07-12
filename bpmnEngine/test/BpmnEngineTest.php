<?php

Phar::interceptFileFuncs();
require_once(__DIR__.'/../build/bpmn.phar');
require_once('BpmnEngineTest_TaskImpls.php');

class BpmnEngineTest extends PHPUnit_Framework_TestCase{

	private $dbAdapter;

	protected function setUp() {

//		$this->dbAdapter = new InMemoryStore();

		// Use this for CouchDB		
// 		$options['host'] = "localhost";
// 		$options['port'] = 5984;
// 		$options['db'] = 'test';
//		$this->dbAdapter = new CouchDbStore($options);
//		$this->dbAdapter->updateDesignDocument();

		// Or use this for File Persistence
		$this->dbAdapter = new \persistence\FileStore();
		
		global $CONFIG;
		$CONFIG->taskImpls[] = CheckVariableA::class;
		$CONFIG->taskImpls[] = ServiceTaskImpl::class;
		$CONFIG->taskImpls[] = UserTaskImpl::class;
		$CONFIG->taskImpls[] = Eins::class;
		$CONFIG->taskImpls[] = Zwei::class;
		$CONFIG->taskImpls[] = Drei::class;
		$CONFIG->taskImpls[] = Vier::class;
		$CONFIG->taskImpls[] = Funf::class;
		$CONFIG->taskImpls[] = Sechs::class;
		$CONFIG->taskImpls[] = Sieben::class;
		$CONFIG->taskImpls[] = Acht::class;
		$CONFIG->taskImpls[] = Neun::class;
		$CONFIG->taskImpls[] = CheckResult::class;
		$CONFIG->eventImpls[] = MessageSendingImpl::class;
		print_r($CONFIG);
	}

	protected function tearDown(){
	}

	public function testGateways(){
		$bpmnEngine = new BpmnEngine($this->dbAdapter);
		$bpmnEngine->importDefinition(file_get_contents(__DIR__.'/GatewayTest.bpmn'));

		$valueMap = array("visits" => "start");
		$process = $bpmnEngine->startProcess("GATEWAY_TEST", $valueMap);
		$result = $process->getResult();
		$this->assertEquals("success", $result);
	}

	public function testTasks(){
		$bpmnEngine = new BpmnEngine($this->dbAdapter);
		$bpmnEngine->importDefinition(file_get_contents(__DIR__.'/TasksTest.bpmn'));

		$valueMap = array("visits" => "start");
		$process = $bpmnEngine->startProcess("TASKS_TEST", $valueMap);
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
//		AbstractMessageEventImpl::registerMessageEventHandler('MessageSendingImpl', new MessageSendingImpl);
	
		$bpmnEngine = new BpmnEngine($this->dbAdapter, "EVENTS_TEST");
		$bpmnEngine->importDefinition(file_get_contents(__DIR__.'/EventTest.bpmn'));

		$valueMap = array("visits" => "start");
		$process = $bpmnEngine->startProcess("EVENTS_TEST", $valueMap);
		$process = $bpmnEngine->continueProcess($process->getId());
		
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
