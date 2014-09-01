<?php
namespace dto;

class Process extends DbObject{
	public function put($key, $value){
		$this->variables = (array)$this->variables;
		$this->variables[$key] = $value;
	}
	public function get($key){
		return isSet($this->variables->$key) ? $this->variables->$key : null;
	}
}
