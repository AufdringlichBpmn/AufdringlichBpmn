<?php

class CouchDBResponse {

	private $raw_response = '';
	private $headers = '';
	private $body = '';

	function __construct($response = '') {
		$this->raw_response = $response;
		list($this->headers, $this->body) = explode("\r\n\r\n", $response);
	}

	function getRawResponse() {
		return $this->raw_response;
	}

	function getHeaders() {
		return $this->headers;
	}

	function getBody() {
		$obj = CouchDB::decode_json($this->body);
		return $obj;
	}
	function getRawBody() {
		return $this->body;
	}
}

class CouchDBRequest {

	static $VALID_HTTP_METHODS = array('DELETE', 'GET', 'POST', 'PUT');

	private $method = 'GET';
	private $url = '';
	private $data = NULL;
	private $sock = NULL;
	private $username;
	private $password;

	function __construct($host, $port = 5984, $url, $method = 'GET', $data = NULL, $username = null, $password = null) {
		$method = strtoupper($method);
		$this->host = $host;
		$this->port = $port;
		$this->url = $url;
		$this->method = $method;
		$this->data = $data ? CouchDB::encode_json($data) : null;
		$this->username = $username;
		$this->password = $password;

		if(!in_array($this->method, self::$VALID_HTTP_METHODS)) {
			throw new Exception('Invalid HTTP method: '.$this->method);
		}
	}

	function getRequest() {
		$req = "{$this->method} {$this->url} HTTP/1.0\r\nHost: {$this->host}\r\n";

		if($this->username || $this->password)
			$req .= 'Authorization: Basic '.base64_encode($this->username.':'.$this->password)."\r\n";

		if($this->data) {
			$req .= 'Content-Length: '.strlen($this->data)."\r\n";
			$req .= 'Content-Type: application/json'."\r\n\r\n";
			$req .= $this->data."\r\n";
		} else {
			$req .= "\r\n";
		}
		return $req;
	}

	private function connect() {
		$this->sock = fsockopen($this->host, $this->port, $err_num, $err_string, 3);
		if(!$this->sock) {
			throw new Exception('Could not open connection to '.$this->host.':'.$this->port.' ('.$err_string.')');
		}
	}

	private function disconnect() {
		fclose($this->sock);
		$this->sock = NULL;
	}

	private function execute() {
		fwrite($this->sock, $this->getRequest());
		$response = '';
		while(!feof($this->sock)) {
			$response .= fgets($this->sock);
		}
		if('' === $response)
			return false;
		$this->response = new CouchDBResponse($response);
		return $this->response;
	}

	function send() {
		$this->connect();
		$response = $this->execute();
		$this->disconnect();
		if(!isSet( $this->response))
			return false;
		return $this->response;
	}

	function getResponse() {
		return $this->response;
	}
}

class CouchDB {

	private $username;
	private $password;

	function __construct($db, $host = 'localhost', $port = 5984, $username = "", $password = "") {
		$this->db = $db;
		$this->host = $host;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
	}

	static function decode_json($str) {
		return json_decode($str);
	}

	static function encode_json($str) {
		return json_encode($str);
	}

	function send($url, $method = 'get', $data = NULL) {
		$url = '/'.$this->db.(substr($url, 0, 1) == '/' ? $url : '/'.$url);
		$request = new CouchDBRequest($this->host, $this->port, $url, $method, $data, $this->username, $this->password);
		return $request->send();
	}

	function get_all_docs() {
		return $this->send('/_all_docs');
	}

	function get_item($id) {
		print $id;
		return $this->send('/'.$id);
	}
}

class CouchDbStore implements ProcessStore {
	private $couch;

	function __construct($options){
		$this->couch = new CouchDB($options['db'], $options['host'], $options['port']);
	}

	public function updateDesignDocument(){
		$designDocumentId = "_design/bpmn";

		$_oldDesignBpmn = $this->couch->send($designDocumentId, "GET")->getBody();
		if(isSet($_oldDesignBpmn->_rev)) {
			$this->couch->send($designDocumentId."?rev=".$_oldDesignBpmn->_rev, "DELETE");
		}
		$_designBpmn["_id"] = $designDocumentId;
		$_designBpmn["language"] = "javascript";
		$_designBpmn["views"]["openTasks"]["map"] =  <<<EOF
function(doc) {
	if(doc.type=='serviceTask'
	&& doc.retries > 0
	&& doc.executed_ts == null){
		emit(doc._id, doc);
	}
	else 	if(doc.type=='userTask'
	&& doc.result
	&& doc.executed_ts == null){
		emit(doc._id, doc);
	}
}
EOF;
		$_designBpmn["views"]["openUserTasksByProcessInstanceId"]["map"] =  <<<EOF
function(doc) {
	if(doc.type=='process_instance'
	&& doc.tasks != null){
		for(var i in doc.tasks){
			var task = doc.tasks[i];
			if(task.type == 'userTask'
			&& task.executedTs == null)
				emit(doc._id, {
					process_instance_id: doc._id,
					task : task,
					variables : doc.variables
				});
		}
	}
}
EOF;
		$_designBpmn["views"]["openUserTasksByProcessInstanceId-20130807"]["map"] =  <<<EOF
function(doc) {
	if(doc.type=='userTask'
	&& doc.result == null){
		emit(doc.process_instance_id, doc);
	}
	else if(doc.type=='process_instance'
	&& doc.result == null){
		emit(doc._id, doc);
	}
}
EOF;
		$_designBpmn["views"]["notExecutedTasks"]["map"] =  <<<EOF
function(doc) {
	if(doc.type.match(/.*Task/)
	&& doc.executed_ts == null){
		emit(doc._id, doc);
	}
}
EOF;
		$_designBpmn["views"]["notExecutedProcessInstances"]["map"] =  <<<EOF
function(doc) {
	if(doc.type=='process_instance'
				&& doc.executed_ts == null){
		for(t in doc.tasks){
			var task = doc.tasks[t];
			if(!task.executedTs && task.result)
				emit(doc._id, doc);
			}
		}
}
EOF;
		$_designBpmn["views"]["by_id"]["map"] =  <<<EOF
function(doc) {
	emit(doc._id, null);
}
EOF;
		$_designBpmn["lists"]["xml"] = <<<EOF
function element(name, row){
	send('<' + name + '>\\n');
	for(field in row) {
		if (field=='content') {

		} else if (field=='executed') {

		} else if (field=='@attributes') {

		} else if (field=='process_definition_xml') {
			send('<process>'+(''+row[field]).replace(/.*process id="([A-Z_]+).*"|.*/gm,'$1').trim() + '</process>');

		} else if (typeof(row[field])=='object') {
			element(field, row[field])
		} else {
			send('<field name=\"' + field + '\">');
// 			send((''+row[field]).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'));
			send((''+row[field]).replace(/<\?.*\?>/g,''));
			send('</field>\\n');
		}
	}
	send('</' + name + '>\\n');
};
function(head, req) {
	provides('xml', function() {
		send('<xml>\\n');
		while (row=getRow()) {
			element('row', row)
		}
		send('</xml>');
	});
};
EOF;
		$_designBpmn["updates"]["createTask"] = <<<EOF
function (doc, req) {
	var task = {};
	task.type = req.query.type;
	task.created_ts = parseInt(req.query.created_ts);
	task.retries = 3;
	task.ref_id = req.query.ref_id;
	task._id = req.query.taskId;
				
	doc.open_tasks.push(task);
	return [doc, "Task erzeugt."];
}
EOF;
		$_designBpmn["updates"]["executeUserTask"] = <<<EOF
function (doc, req) {
	var ref_id = req.query.ref_id;
	var result = req.query.result;
	for(var i in doc.tasks){
		var task = doc.tasks[i];
		if(task.type == 'userTask'
		&& task.ref_id == ref_id){
			task.result = result;
			if(req.query.variables) {
				var variables = JSON.parse(req.query.variables);
				for(var key in variables){
					var value = variables[key];
					doc.variables[key] = value;
				}
			}
		}
	}
	return [doc, "User Task abgeschlossen"];
}
EOF;
		$this->couch->send($_designBpmn["_id"], "PUT", $_designBpmn);
	}

	public function storeProcess($dbObject){
		return $this->storeDbObject($dbObject);
	}
	private function storeDbObject($dbObject){
		$id = $dbObject->getId();
		$resp = $id
		? $this->couch->send($id, "PUT", $dbObject)
		: $this->couch->send('', "POST", $dbObject);
		if($resp)
			$resp =$resp->getBody();
		if(isSet($resp->ok) && $resp->ok){
			$dbObject->_id = $resp->id;
			$dbObject->_rev = $resp->rev;
			return $dbObject;
		} else if($id) {
			//es kam kein response von db, versuche Object zu laden
			return $this->loadDbObject($dbObject, $id);
		}
		print_r($resp);
		return $resp;
	}
	private function loadDbObject($dbObject, $id){
		$dto = $this->couch->send($id, "GET")->getBody();
		if(isSet($dto->_id)){
			$dbObject->merge($dto);
			return $dbObject;
		}
		return false;
	}
	public function deleteDbObject($dbObject){
		$result = $this->couch->send($dbObject->getId()."?rev=".$dbObject->_rev, "DELETE")->getBody();
		// 		print_r($result);
	}
	public function loadTask( $id){
		return $this->loadDbObject(new Task(), $id);
	}
	public function loadProcessDefinition( $id){
		return $this->loadDbObject(new ProcessDefinition(), $id);
	}
	public function loadProcess( $id){
		$process = $this->loadDbObject(new Process(), $id);
		$tasks = array();
		foreach($process->tasks as $index => $dto){
			$tasks[] = $task = new Task();
			$task->merge($dto);
		}
		$process->tasks = $tasks;
		return $process;
	}

	public function importDefinition($process_definition_xml) {
		$simpleXml = new SimpleXMLElement($process_definition_xml);
		$simpleXml->registerXPathNamespace("bpmn", "http://www.omg.org/spec/BPMN/20100524/MODEL");

		foreach($simpleXml->process as $process) {
			$pdId = "".$process->attributes()->id;
			$processDefinition = $this->loadProcessDefinition($pdId);
			if($processDefinition) {
				$processDefinition->xml = $simpleXml->asXML();
			} else {
				$processDefinition = new ProcessDefinition(array(
						'type' => "process_definition",
						'_id' => $pdId,
						'xml' => $simpleXml->asXML()
				));
			}
			$this->storeDbObject( $processDefinition );
		}
	}

	public function listProcessDefinitions(){
		// TODO implement this
	}
	public function findOpenUserTasks(){
		// TODO implement this
	}

	public function put($processInstanceId, $key, $value){
		$process = $this->loadProcess($processInstanceId);
		$process->variables = ((array)$process->variables);
		$process->variables[$key] = $value;
		$this->storeDbObject($process);
	}

	public function get($processInstanceId, $key){
		$process = $this->loadProcess($processInstanceId);
		return isSet($process->variables->$key) ? $process->variables->$key : null;
	}

	public function getTaskResult($processInstanceId, $element){
		$taskId = "task:".$processInstanceId.':'.$element->attributes()->id;
		$task = $this->couch->send($taskId, "GET")->getBody();
		return $task->result;
	}
	public function decrementTaskRetries($taskId){
		$task = $this->couch->send($taskId, "GET")->getBody();
		$task->retries--;
		$this->couch->send($taskId, "PUT", $task);
	}
	public function setExceptionMessage($taskId, $message){
		$task = $this->couch->send($taskId, "GET")->getBody();
		$task->exception_message = $message;
		$this->couch->send($taskId, "PUT", $task);
	}

	public function checkParallelGateReady($processInstanceId, $refId ){
		$taskId = "task:".$processInstanceId.':'.$refId;
		$task = $this->loadTask($taskId);
		if($task){
			return isSet($task->executed_ts);
		}
		$process = $this->loadProcess($processInstanceId);
		if(isSet($process->executed)){
			$process->executed = (array)$process->executed;
			foreach($process->executed as $executedTs => $task){
				if($taskId == $task->_id)
					return true;
			}
		}
		return false;
	}
	public function markTaskExecuted($taskId, $result){
		$task = $this->loadTask($taskId);
		$task->executed_ts = time();
		$task->result = $result;
		{// safe executed Tasks in Process
			$process = $this->loadProcess($task->process_instance_id);
			if(isSet($process->executed)) {
				$process->executed  = (array) $process->executed;
			}
			$process->executed[time()] = $task;
			$this->storeDbObject($process);
		}
		$this->deleteDbObject($task);
	}

	public function markProcessInstanceExecuted($processInstanceId, $result){
		$params = '?startkey=%22task:'.$processInstanceId.':%22&endkey=%22task:'.$processInstanceId.':X%22';
		$body = $this->couch->send('/_design/bpmn/_view/notExecutedTasks'.$params, "GET")->getBody();
		if(isSet($body->rows))
		foreach($body->rows as $key => $row) {
			$task = $row->value;
			$task->executed_ts = -1;
			$this->couch->send($row->id, "PUT", $task);
		}
		$processInstance = $this->couch->send($processInstanceId, "GET")->getBody();
		$processInstance->executed_ts = time();
		$processInstance->result = $result;
		$this->couch->send($processInstanceId, "PUT", $processInstance);
	}

	public function getProcessInstanceResult($processInstanceId) {
		$process = $this->loadProcess($processInstanceId);
		return isSet($process->result) ? $process->result : null;
	}

	public function createProcessInstance($processDefinitionName, $variables){
		$processDefinitionId = $processDefinitionName;
		$processDefinition = $this->loadProcessDefinition($processDefinitionId);
		$process = new Process(array(
				'_id' => $processDefinitionName.":".md5(''.time()),
				'type' => "process_instance",
				'open_tasks' => array(),
				'variables' => $variables,
				'process_definition_xml' => $processDefinition->xml,
				'created_ts' => time()
		));
		$process = $this->storeDbObject($process);
		// read ID and add Variables and write back
		$process->put("_guid", $process->getId());
		$process = $this->storeDbObject($process);
		return $process;
	}

	public function getProcessInstanceIdByGuid($guid){
		return $guid;
	}

	public function findNotExecutedProcessInstanceIds(){
		$body = $this->couch->send('/_design/bpmn/_view/notExecutedProcessInstances')->getBody();
		if(isSet($body->rows)){
			$piIds = array();
			foreach($body->rows as $key => $row) {
				$piIds[$key] = $row->id;
			}
			return $piIds;
		}
		return false;
	}

	public function executeUserTaskByRefId($processInstanceId, $refId){
		foreach($this->couch->get_all_docs()->getBody()->rows as $r => $row) {
			$docId = $row->id;
			if(eregi("^task:".$processInstanceId().':', $docId)) {
				$task = $this->couch->get_item($docId)->getBody();
				if( ! $task->executed_ts && $task->ref_id == $refId) {
					$task->executed_ts = time();
					$this->couch->send($docId, "PUT", $task);
					return true;
				}
			}
		}
		return false;
	}

	public function findNextServiceTask($processInstanceId){
		$params = '?startkey=%22task:'.$processInstanceId.':%22&endkey=%22task:'.$processInstanceId.':X%22';
		$body = $this->couch->send('/_design/bpmn/_view/openTasks'.$params, "GET")->getBody();
		if(isSet($body->rows))
		foreach($body->rows as $key => $row) {
			return new Task($row->value);
		}
		return false;
	}
}

