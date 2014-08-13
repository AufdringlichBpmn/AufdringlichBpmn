<?php

class XmlAdapter {

	private $simpleXml;

	function setProcessDefinitionXml($process_definition_xml){
		$this->process_definition_xml = $process_definition_xml;
		$this->simpleXml = new SimpleXMLElement($this->process_definition_xml);
		$this->simpleXml->registerXPathNamespace("bpmn", "http://www.omg.org/spec/BPMN/20100524/MODEL");
		// initial handlers
		foreach($this->simpleXml->xpath("//import[@importType='php']") as $importElement){
			require_once($this->getAttribute($importElement, 'location'));
		}		
	}

	public function findStartEventElement($processElement = null){
		$processElement = $processElement ? $processElement : $this->simpleXml->xpath("//bpmn:process/bpmn:startEvent");
		foreach($processElement as $startEvent){
			return $startEvent;
		}
	}

	public function findElementById($elementId){
		foreach($this->simpleXml->xpath("//*[@id='".$elementId."']") as $element){
			return $element;
		}
	}

	public function findSequenceFlowElementsBySourceElementExcludeDefault($element, $defaultId){
		$elementId =  $this->getAttribute($element, "id");
		return $this->simpleXml->xpath("//bpmn:sequenceFlow[@sourceRef='".$elementId."'][@id!='".$defaultId."']");
	}
	public function findSequenceFlowElementsBySourceElement($element){
		$elementId =  $this->getAttribute($element, "id");
		return $this->simpleXml->xpath("//bpmn:sequenceFlow[@sourceRef='".$elementId."']");
	}
	public function findSequenceFlowElementsByTargetElement($element){
		$elementId =  $this->getAttribute($element, "id");
		return $this->simpleXml->xpath("//bpmn:sequenceFlow[@targetRef='".$elementId."']");
	}
	function findBoundaryEventElementsByRefElement($element){
		$elementId =  $this->getAttribute($element, "id");
		return $this->simpleXml->xpath("//bpmn:boundaryEvent[@attachedToRef='".$elementId."']");
	}

	public function getAttribute($element, $attribute) {
		return (String) $element->attributes()->$attribute;
	}
	public function getAttributes($element) {
		return (array) $element->attributes();
	}
	public function getName($element) {
		return (String) $element->getName();
	}

}
