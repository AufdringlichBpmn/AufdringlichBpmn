<?php
require_once("../BpmnEngine.php");
require_once("../FileStore.php");

class FileStoreTest extends PHPUnit_Framework_TestCase{

	private $testee;
	private $process_definition_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<definitions xmlns="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" xmlns:di="http://www.omg.org/spec/DD/20100524/DI" xmlns:tns="http://sourceforge.net/bpmn/definitions/_1349295267926" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:yaoqiang="http://bpmn.sourceforge.net" exporter="Yaoqiang BPMN Editor" exporterVersion="2.1.3" expressionLanguage="http://www.w3.org/1999/XPath" id="_1349295267926" name="" targetNamespace="http://sourceforge.net/bpmn/definitions/_1349295267926" typeLanguage="http://www.w3.org/2001/XMLSchema" xsi:schemaLocation="http://www.omg.org/spec/BPMN/20100524/MODEL http://bpmn.sourceforge.net/schemas/BPMN20.xsd">
  <itemDefinition id="ID_1" isCollection="false" itemKind="Information" structureRef="info1"/>
  <process id="PROCESS_1" isClosed="false" isExecutable="true" name="testProcess" processType="None">
    <startEvent id="_2" isInterrupting="true" name="Start Event" parallelMultiple="false">
      <outgoing>_9</outgoing>
    </startEvent>
    <endEvent id="_4" name="End Event">
      <incoming>_14</incoming>
    </endEvent>
    <callActivity calledElement="GT_1" completionQuantity="1" id="_7" isForCompensation="false" name="Increment A" startQuantity="1">
      <incoming>_10</incoming>
      <incoming>_13</incoming>
      <outgoing>_16</outgoing>
    </callActivity>
    <exclusiveGateway default="_18" gatewayDirection="Diverging" id="_11" name="5?">
      <incoming>_12</incoming>
      <outgoing>_18</outgoing>
      <outgoing>_13</outgoing>
    </exclusiveGateway>
    <exclusiveGateway gatewayDirection="Converging" id="_15">
      <incoming>_16</incoming>
      <incoming>_18</incoming>
      <outgoing>_14</outgoing>
    </exclusiveGateway>
    <sequenceFlow id="_16" sourceRef="_7" targetRef="_15"/>
    <sequenceFlow id="_18" sourceRef="_11" targetRef="_15"/>
    <serviceTask completionQuantity="1" id="_8" implementation="CheckVariableA" isForCompensation="false" name="Check Variable A" startQuantity="1">
      <incoming>_9</incoming>
      <outgoing>_12</outgoing>
    </serviceTask>
    <sequenceFlow id="_9" sourceRef="_2" targetRef="_8"/>
    <sequenceFlow id="_12" sourceRef="_8" targetRef="_11"/>
    <sequenceFlow id="_13" name="yes" sourceRef="_11" targetRef="_7"/>
    <sequenceFlow id="_14" sourceRef="_15" targetRef="_4"/>
  </process>
  <globalScriptTask id="GT_1" name="incNumbers" scriptLanguage="text/x-groovy">
    <script><![CDATA[Numbers::incrementA($context);]]></script>
  </globalScriptTask>
</definitions>';

	protected function setUp() {
		$this->testee = new FileStore();
	}

	protected function tearDown(){
	}

	public function testImportDefinition(){
		$this->testee->importDefinition($this->process_definition_xml);
		$pd = $this->testee->loadProcessDefinition("PROCESS_1");
		$this->assertNotNull($pd);
		
		$pds = $this->testee->listProcessDefinitions();
		$this->assertNotNull($pds);
		print_r($pds[0]->getId());
	}
	

}
