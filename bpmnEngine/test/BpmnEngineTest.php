<?php
require_once("../BpmnEngine.php");
require_once("../InMemoryStore.php");
// require_once("../CouchDbStore.php");
// require_once("../FileStore.php");

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
		$this->dbAdapter = new FileStore();
	}

	protected function tearDown(){
	}

	public function testGateways(){
		$bpmnEngine = new BpmnEngine($this->dbAdapter, "GATEWAY_TEST");
		$bpmnEngine->importDefinition(file_get_contents('GatewayTest.bpmn'));

		$valueMap = array("visits" => "start");
		$process = $bpmnEngine->startProcess($valueMap);
		$result = $process->getResult();
		$this->assertEquals("success", $result);
	}

	public function testTasks(){
		$bpmnEngine = new BpmnEngine($this->dbAdapter, "TASKS_TEST");
		$bpmnEngine->importDefinition(file_get_contents('TasksTest.bpmn'));

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
	
}

class TestUserTask{
	static function preProcessUserTask($engine, $processInstanceId, $elementId){
	}
}
class AbstractServiceTaskImpl{
	protected $process, $element;
	function init($process, $element){
		$this->process = $process;
		$this->element = $element;
	}
}
class CheckVariableA extends AbstractServiceTaskImpl{
	function processServiceTask(){
		return  $this->process->get( "a");
	}
}
// Tasks
class ServiceTaskImpl extends AbstractServiceTaskImpl{
	function processServiceTask(){
		return "success";
	}
}
class UserTaskImpl extends AbstractServiceTaskImpl{
	static $testProcessInstanceId;
	static function preProcessUserTask($engine, $processInstanceId, $elementId){
		self::$testProcessInstanceId=$processInstanceId;
		print_r("called UserTaskImpl::preProcessUserTask(engine, $processInstanceId, $elementId)");
	}
}

// Gateways
class Eins extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 1");
		return 1;
	}
}
class Zwei extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 2");
		return 2;
	}
}
class Drei extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 3");
		return 3;
	}
}
class Vier extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 4");
		return 4;
	}
}
class Funf extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 5");
		return 5;
	}
}
class Sechs extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 6");
		return 6;
	}
}
class Sieben extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 7");
		return 7;
	}
}
class Acht extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 8");
		return 8;
	}
}
class Neun extends AbstractServiceTaskImpl{
	function processServiceTask(){
		$this->process->put( "visits", $this->process->get( "visits").", 9");
		return 9;
	}
}
class CheckResult extends AbstractServiceTaskImpl{
	function processServiceTask(){
		return $this->process->get( "visits");
	}
}

?>
