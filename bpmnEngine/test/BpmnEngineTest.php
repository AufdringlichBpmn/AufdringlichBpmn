<?php
require_once "PHPUnit/Autoload.php";
require("../BpmnEngine.php");

class BpmnEngineTest extends PHPUnit_Framework_TestCase{
	private $db;

	protected function setUp() {
		$this->db = new PDO('sqlite:BpmnEngineTest.sqlite');
// 		$this->db->beginTransaction();
		
		//remove database
		$this->db->exec("DROP TABLE variable_map");
		$this->db->exec("DROP TABLE task");
		$this->db->exec("DROP TABLE process_instance");
		$this->db->exec("DROP TABLE process_definition");

	}

	protected function tearDown(){
//		$this->db->commit();
		$ausgabe = array();
		exec ("sqlite3 -column -header BpmnEngineTest.sqlite 'select * from process_instance order by id;'", $ausgabe);
		exec ("sqlite3 -column -header BpmnEngineTest.sqlite 'select * from variable_map;'", $ausgabe);
		exec ("sqlite3 -column -header BpmnEngineTest.sqlite 'select * from task;'", $ausgabe);
		print_r( $ausgabe);
		//remove database
		$this->db->exec("DROP TABLE variable_map");
		$this->db->exec("DROP TABLE task");
		$this->db->exec("DROP TABLE process_instance");
		$this->db->exec("DROP TABLE process_definition");
	}

	public function testBpmnEngine(){
		$bpmnEngine = new BpmnEngine($this->db);
		$bpmnEngine->importDefinition('BpmnEngineTest.bpmn');
		
		$valueMap = array("aa"=>2, "bb"=>3);
		$process = $bpmnEngine->startProcessByName("PROCESS_1", $valueMap);
		
		$bpmnEngine->listUserTasks();
	}
	
	public function testLast(){
		$bpmnEngine = new BpmnEngine($this->db);
		$bpmnEngine->importDefinition('BpmnEngineTest.bpmn');
	
		for($i=0;$i<2;$i++){
			$this->db->beginTransaction();
			$valueMap = array("a"=>2, "b"=>$i);
			$process = $bpmnEngine->startProcessByName("PROCESS_1", $valueMap);
			while($process->processNextServiceTask());
			$this->db->commit();
		}

		$userTasks = $bpmnEngine->listUserTasks();
		foreach($userTasks as $userTask){
			$process = $bpmnEngine->getProcessInstanceByGuid($userTask["guid"]);
			$process->executeUserTaskByRefId($userTask["ref_id"]);
//			$process->executeUserTaskByRefId($userTask["ref_id"]);
			while($process->processNextServiceTask());
		}
	}
}


class NumberAdder{
	static function processServiceTask($engine, $processInstanceId, $elementId){
	}
	static function addNumbers($ctx){
		$a = $ctx->get("a");
		$b = $ctx->get("b");
		$ctx->put("c", $a+$b);
	}
	static function addNumbers2($ctx){
		$a = $ctx->get("a");
		$b = $ctx->get("b");
		$c = $ctx->get("c");
		$ctx->put("d", $a+$b+$c);
		if($a==null) throw new Exception("Ausnahme aufgetreten.");
	}
}
?>