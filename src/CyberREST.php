<?php
/**
 * Name: CyberREST
 * Description: Helper Class to create a REST API
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 * @copyright 2015 NetRunners.es
 * @version 1.0
 * @author Miguel S. Mendoza <miguel@smendoza.net>
 **/
 
class CyberConfig {
	public $API;
	public $Secret;
	public $ServerName;
	
	public function __construct($apiStart= "API", $secretKey = "", $servername = "") {
		$this->API = $apiStart;
		$this->Secret = $secretKey;
		$this->ServerName = $servername;
	}
 }
 
class CyberREST {

	public $_allow = array();
	public $_content_type = "application/json";
	public $_request = array();
	public $_format = "json";
	
	private $JWTKey = "";
	private $ServerName = "";
	private $apiStart = "API";
	private $requestParts = array();
	private $parameters = array();
	private $patternParts = array();

	private $_method = "";
	private $_code = 200;
	
	private $server;

	public function __construct($config= "API") {
		if(is_a($config, 'CyberConfig')) {
			$this->apiStart = $config->API;
			$this->JWTKey = $config->Secret;
			$this->ServerName = $config->ServerName;
		} else {
			$this->apiStart = $apiStart;
		}
		$this->inputs();
		$this->requestParts = $this->getRequestPartsFrom($this->apiStart);
		$this->parameters = $this->parseIncomingParams();
	}
		
	public function authorizeRequest() {
		$headers = apache_request_headers();
		if(!isset($headers['authorization'])) {
			return NULL;
		}
		$authHeader = $headers['Authorization'];
		try {
			list($jwt) = sscanf($authHeader, 'Authorization: Bearer %s');
			$secretKey = base64_decode($this->JWTKey);
			$token = Firebase\JWT\JWT::decode($jwt, $secretKey, array('HS512'));
			return $token;
		} catch (Exception $e) {
            return NULL;
       }
       return NULL;
	}
	
	function createToken($data) {
		$tokenId    = base64_encode(mcrypt_create_iv(32, MCRYPT_RAND));
		$issuedAt   = time();
		$notBefore  = $issuedAt + 10;
		$expire     = $notBefore + 60;
		$serverName = $this->ServerName; 

		$data = [
			'iat'  => $issuedAt,
			'jti'  => $tokenId,
			'iss'  => $serverName,
			'nbf'  => $notBefore,
			'exp'  => $expire,
			'data' => $data
		];
		$secretKey = base64_decode($this->JWTKey);
		
		$jwt = Firebase\JWT\JWT::encode(
			$data,
			$secretKey, 
			'HS512' 
		);
		
		return $jwt;
	}
	
	public function checkRefererWhiteList($whitelist=array()) {
		$refererHost = parse_url($this->getReferer(), PHP_URL_HOST);
		$in = in_array($refererHost, $whitelist);
		$wowww = str_replace("www.", "", $refererHost);
		$inwowww = in_array($wowww, $whitelist);
		return ($in || $inwowww);
	}
	
	public function getApiStart() {
		return $this->apiStart;
	}
	
	public function getRequestParts() {
		return $this->requestParts;
	}
	
	public function getParameters() {
		return $this->parameters;
	}
	
	public function getPatternParts() {
		return $this->patternParts;
	}
	
	public function get($pattern, $function) {
		return $this->processRequest("GET", $pattern, $function);
	}
	
	public function post($pattern, $function) {
		return $this->processRequest("POST", $pattern, $function);
	}
	
	public function put($pattern, $function) {
		return $this->processRequest("PUT", $pattern, $function);
	}
	
	public function delete($pattern, $function) {
		return $this->processRequest("DELETE", $pattern, $function);
	}
	
	private function processRequest($method, $pattern, $function) {
		if($this->checkValidRequest($method, $pattern)) {
			$params = $this->getURIParameters();
			array_unshift($params, $this);
			call_user_func_array($function, $params);
			return true;
		} 
		return false;
	}

	private function checkValidRequest($method, $pattern) {
		return $this->getRequestMethod()==$method && $this->checkPattern($pattern);
	}
	
	private function isParameter($string) {
		if(preg_match('/{\w+}/', $string)>0) 
			return true;
		return false;
	}
	
	private function cleanParameter($param) {
		return str_replace(['{','}'], '', $param);
	}

	private function storeParameterWithValue($param,$value) {
		$key = $this->cleanParameter($param);
		$this->parameters[$key] = $value;
	}
	
	private function getURIParameters () {
		$params = array();
		$count = count($this->patternParts);
		for($i=0;$i<$count;$i++) {
			if($this->isParameter($this->patternParts[$i])) {
				$key = $this->cleanParameter($this->patternParts[$i]);
				$params[$key] = $this->requestParts[$i];
				$this->storeParameterWithValue($this->patternParts[$i], $this->requestParts[$i]);
			} 
		}
		return $params;
	}
	
	public function parsePattern($pattern) {
		$parts = explode('/', $pattern);
		if(!isset($parts[0])) return array();
		if($parts[0]==="") { 
			unset($parts[0]);
			$parts = array_values($parts);
		}
		return $parts;
	}
	
	private function checkPattern($pattern) {
		$parts = $this->parsePattern($pattern);
		if(count($parts)==count($this->requestParts)) {
			$count = count($parts);
			for($i=0;$i<$count;$i++) {
				if(!$this->isParameter($parts[$i]) 
					&& 
				($parts[$i] !== $this->requestParts[$i])) {
					return false;
				}
			}
			$this->patternParts = $parts;
			return true;
		} 
		return false;
	}
	
	public function getClientIP() {
		$ip="";
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		    $ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
		    $ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	public function getReferer(){
		if(isset($_SERVER['HTTP_REFERER']))
			return $_SERVER['HTTP_REFERER'];
		else 
			return "";
	}

	public function response($data,$status){
		$this->_code = ($status)?$status:200;
		$this->setHeaders();
		echo $this->parseResponse($data);
		die();
	}
	
	private function parseResponse($data) {
		if(!is_array($data) && !is_object($data)) {
			$data = ["response"=>$data];
		}
		$response = json_encode($data);
		return $response;
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
		if(isset($_SERVER['REQUEST_METHOD']))
			return $_SERVER['REQUEST_METHOD'];
		else
			return '';
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
			//$this->response('Bad Request',200);
			break;
		}
	}

	private function cleanInputs($data){
		$clean_input = array();
		if(is_array($data)){
			foreach($data as $k => $v){
				$clean_input[$k] = $this->cleanInputs($v);
			}
		} else{
			if(get_magic_quotes_gpc()){
				$data = trim(stripslashes($data));
			}
			$data = strip_tags($data);
			$clean_input = trim($data);
		}
		return $clean_input;
	}


	private function setHeaders(){
		if(!headers_sent()){
			header("HTTP/1.1 ".$this->_code." ".$this->getStatusMessage());
			header("Content-Type:".$this->_content_type."; charset=utf-8");
		}
	}

	public function parseIncomingParams() {
		$parameters = array();
		if($this->getRequestMethod()=="GET") {
				$parameters = $this->parseGETParams();
		} else {
				$parameters = $this->parseParams();
			}
		return $parameters;
	}
	
	private function parseGETParams() {
		$parameters = array();
		if (isset($_SERVER['QUERY_STRING'])) {
			parse_str($_SERVER['QUERY_STRING'], $parameters);
		}
		return $parameters;
	}
	
	private function parseParams() {
		$parameters = array();
		$body = file_get_contents("php://input");
		$content_type = false;
		if(isset($_SERVER['CONTENT_TYPE'])) {
			$content_type = $_SERVER['CONTENT_TYPE'];
		}
		if(strpos($content_type, "application/json") !== false) {
			$body_params = json_decode($body);
			if($body_params) {
				foreach($body_params as $param_name => $param_value) {
					$parameters[$param_name] = $param_value;
				}
			}
			$this->format = "json";
		} else if(strpos($content_type, "application/x-www-form-urlencoded") !== false) {
			parse_str($body, $postvars);
			foreach($postvars as $field => $value) {			
				$parameters[$field] = $value;
			}
			$this->format = "html";
		} else {
			parse_str($body, $postvars);
			foreach($postvars as $field => $value) {			
				$parameters[$field] = $value;
			}
			$this->format = "html";
		}
		return $parameters;
	}

	public function getRequestPartsFrom($apiStart = 'API') {
		$uri = "";
		if(!isset($_SERVER['REQUEST_URI']))
			$uri ="/";
		else if (strpos( $_SERVER['REQUEST_URI'], '?') !== false) 
		    $uri = substr($_SERVER['REQUEST_URI'], 0, strpos( $_SERVER['REQUEST_URI'], '?'));
		else 
			$uri = $_SERVER['REQUEST_URI'];
		$parts = explode('/', $uri);
		$index = 0;
		while(isset($parts[$index]) && $parts[$index]!=$apiStart) {
			unset($parts[$index]);
			$index++;
		}
		unset($parts[$index]);
		if(isset($parts[$index+1]) && ((strpos($parts[$index+1],".php")!==false))) unset($parts[$index+1]);
		return array_values($parts);
	}

	public function encodeJSONforHTML($array) {
		if(is_array($array))
		array_walk_recursive($array, function(&$item, $key) {
				if(is_string($item)) {
					$item = htmlentities($item);
				}
			});
		else
			$array = array();
		return json_encode($array);
	}
	
	function getHeaders() 
    { 
           $headers = ''; 
       foreach ($_SERVER as $name => $value) 
       { 
           if (substr($name, 0, 5) == 'HTTP_') 
           { 
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
           } 
       } 
       return $headers; 
    } 
}
?>
