<?php

namespace elements;

interface BpmnElementHandler{
	static function canHandleElement($elementName);
}
