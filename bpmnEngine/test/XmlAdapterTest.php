<?php
require_once "PHPUnit/Autoload.php";
require("../XmlAdapter.php");

class XmlAdapterTest extends PHPUnit_Framework_TestCase{
	protected function setUp() {
	}

	protected function tearDown(){
	}

	public function test1(){
		$xml = file_get_contents("BpmnEngineTest001.bpmn");
		$xmlAdapter = new XmlAdapter($xml);
		print_r($xmlAdapter->findStartEventElement());
		print_r($xmlAdapter->findSequenceFlowElementsBySourceElement("_11"));
		print_r($xmlAdapter->findSequenceFlowElementsByTargetElement("_7"));
		print_r($xmlAdapter->findElementById("_7"));
		print_r(false == $xmlAdapter->findElementById("_7XXX"));
		print_r($xmlAdapter->getAttribute($xmlAdapter->findElementById("_7"),"id"));
	}

	public function testMerge(){
		$task = new Task();
		$taskDto = json_decode('{"name":"serviceTask"}');
		foreach((array)$taskDto as $key => $value){
			$task->$key = $value;
		}
		print $task->getName();
		print "\n";
		print json_encode($task);
		print "\n";
	}
	
}

class Task {
	public function getName(){
		return $this->name;
	}
}