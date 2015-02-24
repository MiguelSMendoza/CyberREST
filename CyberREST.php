<?php
/**
 * Name: CyberREST
 * Description: Helper Class to create a REST API
 * @copyright 2014 SMendoza.net
 * @license    http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 * @link       https://github.com/MiguelSMendoza/CyberREST
 * @version 1.0
 * @author Miguel S. Mendoza <miguel@smendoza.net>
 **/
 class CyberREST {

	public $_allow = array();
	public $_content_type = "application/json";
	public $_request = array();
	public $_format = "json";
	public $parameters= array();

	private $_method = "";
	private $_code = 200;

	public function __construct(){
		$this->inputs();
	}

	public function getReferer(){
		return $_SERVER['HTTP_REFERER'];
	}

	public function response($data,$status){
		$this->_code = ($status)?$status:200;
		$this->setHeaders();
		echo $data;
		exit;
	}

	private function getStatusMessage(){
		$status = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => '(Unused)',
			307 => 'Temporary Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported');
		return ($status[$this->_code])?$status[$this->_code]:$status[500];
	}

	public function getRequestMethod(){
		return $_SERVER['REQUEST_METHOD'];
	}

	private function inputs(){
		switch($this->getRequestMethod()){
		case "POST":
			$this->_request = $this->cleanInputs($_POST);
			break;
		case "GET":
		case "DELETE":
			$this->_request = $this->cleanInputs($_GET);
			break;
		case "PUT":
			parse_str(file_get_contents("php://input"),$this->_request);
			$this->_request = $this->cleanInputs($this->_request);
			break;
		default:
			$this->response('',406);
			break;
		}
	}

	private function cleanInputs($data){
		$clean_input = array();
		if(is_array($data)){
			foreach($data as $k => $v){
				$clean_input[$k] = $this->cleanInputs($v);
			}
		}else{
			if(get_magic_quotes_gpc()){
				$data = trim(stripslashes($data));
			}
			$data = strip_tags($data);
			$clean_input = trim($data);
		}
		return $clean_input;
	}


	private function setHeaders(){
		header("HTTP/1.1 ".$this->_code." ".$this->getStatusMessage());
		header("Content-Type:".$this->_content_type);
	}

	public function parseIncomingParams() {
		$parameters = array();

		if (isset($_SERVER['QUERY_STRING'])) {
			parse_str($_SERVER['QUERY_STRING'], $parameters);
		}

		// now how about PUT/POST bodies? These override what we got from GET

		$body = file_get_contents("php://input");
		$content_type = false;
		if(isset($_SERVER['CONTENT_TYPE'])) {

			$content_type = $_SERVER['CONTENT_TYPE'];
		}
		switch($content_type) {
		case "application/json":
			$body_params = json_decode($body);
			if($body_params) {
				foreach($body_params as $param_name => $param_value) {
					$parameters[$param_name] = $param_value;
				}
			}
			$this->format = "json";
			break;
		case "application/x-www-form-urlencoded":
			parse_str($body, $postvars);
			foreach($postvars as $field => $value) {
				$parameters[$field] = $value;

			}
			$this->format = "html";
			break;
		default:
			foreach($_POST as $field => $value) {
				$parameters[$field] = $value;
			}
			break;
		}
		$this->parameters = $parameters;
		return $this->parameters;
	}

	public function getRequestPartsFrom($apiStart = 'API') {
		$uri = substr($_SERVER['REQUEST_URI'], 0, strpos( $_SERVER['REQUEST_URI'], '?'));
		$parts = explode('/', $uri);
		$index = 0;
		while($parts[$index]!=$apiStart) {
			unset($parts[$index]);
			$index++;
		}
		return array_values($parts);
	}

	public function encodeJSONforHTML($array)
	{
		array_walk_recursive($array, function(&$item, $key) {
				if(is_string($item)) {
					$item = htmlentities($item);
				}
			});
		return json_encode($array);
	}
}
?>
