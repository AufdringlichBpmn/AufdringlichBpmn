<?php

namespace dto;

class DbObject{
	public function __construct($dto = array()){
		$this->merge($dto);
	}
	public function merge($dto){
		foreach((array)$dto as $key => $value){
			if("\0" != substr($key, 0, 1))
				$this->$key = $value;
		}
	}
	public function getRefId(){
		return $this->ref_id;
	}
	public function getId(){
		return isSet($this->_id) ? $this->_id : null;
	}
}
