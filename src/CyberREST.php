<?php
/**
 * Name: CyberREST
 * Description: Helper Class to create a REST API
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 * @copyright 2015 NetRunners.es
 * @version 1.5
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

 class Environment {
	 public static function getServer() {
		 return $_SERVER;
	 }
	 public function getServerVar($var='') {
		 if (isset($_SERVER[$var])) {
			 return $_SERVER[$var];
		 }
		 return '';
	 }
 }

 class OAuth {
	private $Firebase;
	private $jwtKey = "";
	private $ServerName = "";

	public function setServerData($name, $key) {
		$this->jwtKey = $key;
		$this->ServerName = $name;
	}

	public function __construct($serverName="CyberREST", $jwtKey="") {
		$this->Firebase = new Firebase\JWT\JWT();
		$this->ServerName = $serverName;
		$this->jwtKey = $jwtKey;
	}

	public function getAuthHeader() {
		$headers = Environment::getServer();
		// $headers = apache_request_headers();
		$authHeader = false;
		if(isset($headers['Authorization'])) {
			$authHeader = $headers['Authorization'];
		} else if (isset($headers['authorization'])) {
			$authHeader = $headers['authorization'];
		}
		return $authHeader;
	}

	/* throws ExpiredException */	
	public function authorizeRequest() {
		$authHeader=$this->getAuthHeader();
		if(!$authHeader) {
			throw new Exception('Not Authorized');
			return false;
		}
		list($jwt) = sscanf($authHeader, 'Bearer %s');
		$secretKey = base64_decode($this->jwtKey);
		$token = $this->Firebase->decode($jwt, $secretKey, array('HS512'));
		$newToken = $this->createToken($token->data);
		return ["token"=>$newToken, "data"=>$token->data];
	}

	function createToken($data) {
		$tokenId    = base64_encode(mcrypt_create_iv(32, MCRYPT_RAND));
		$issuedAt   = time();
		$notBefore  = $issuedAt;
		$expire     = $notBefore + 604800;
		$serverName = $this->ServerName; 

		$data = [
			'iat'  => $issuedAt,
			'jti'  => $tokenId,
			'iss'  => $serverName,
			'nbf'  => $notBefore,
			'exp'  => $expire,
			'data' => $data
		];
		$secretKey = base64_decode($this->jwtKey);
		
		$jwt = $this->Firebase->encode(
			$data,
			$secretKey, 
			'HS512' 
		);
		return $jwt;
	}

 }
 
class CyberREST {

	public $ContentType = "application/json";
	public $Request = array();
	
	private $apiStart = "API";
	private $requestParts = array();
	private $parameters = array();
	private $patternParts = array();
	public $OAuth;
	public $Environment;

	private $code = 200;

	public function __construct($config= "API") {
		$this->apiStart = $config;
		$this->Environment = new Environment();
		$this->OAuth = new OAuth();
		if(is_a($config, 'CyberConfig')) {
			$this->apiStart = $config->API;
			$this->OAuth->setServerData($config->ServerName, $config->Secret);
		}
		$this->inputs();
		$this->requestParts = $this->getRequestPartsFrom($this->apiStart);
		$this->parameters = $this->parseIncomingParams();
	}
	
	public function checkRefererWhiteList($whitelist=array()) {
		$refererHost = parse_url($this->getReferer(), PHP_URL_HOST);
		$inArray = in_array($refererHost, $whitelist);
		$wowww = str_replace("www.", "", $refererHost);
		$inwowww = in_array($wowww, $whitelist);
		return ($inArray || $inwowww);
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
	
	private function parsePattern($pattern) {
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
		$clientIP="";
		$httpClientIp = $this->Environment->getServerVar('HTTP_CLIENT_IP');
		$httpXForwardedFor = $this->Environment->getServerVar('HTTP_X_FORWARDED_FOR');
		$remoteAddr = $this->Environment->getServerVar('REMOTE_ADDR');
		if (!empty($httpClientIp)) {
		    $clientIP = $httpClientIp;
		} elseif (!empty($httpXForwardedFor)) {
		    $clientIP = $httpXForwardedFor;
		} elseif (!empty($remoteAddr)) {
		    $clientIP = $remoteAddr;
		}
		return $clientIP;
	}

	public function getReferer(){
		return $this->Environment->getServerVar('HTTP_REFERER');
	}

	public function response($data,$status){
		$this->code = ($status)?$status:200;
		$this->setHeaders();
		echo $this->parseResponse($data);
		// die($this->code);
	}
	
	private function parseResponse($data) {
		$response = $data;
		if(is_array($data) || is_object($data)) {
			$response = json_encode($data);
		}
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
		return ($status[$this->code])?$status[$this->code]:$status[500];
	}

	public function getRequestMethod(){
		return $this->Environment->getServerVar('REQUEST_METHOD');
	}

	private function inputs(){
		switch($this->getRequestMethod()){
		case "POST":
			$this->Request = $this->cleanInputs($_POST);
			break;
		case "GET":
		case "DELETE":
			$this->Request = $this->cleanInputs($_GET);
			break;
		case "PUT":
			parse_str(file_get_contents("php://input"),$this->Request);
			$this->Request = $this->cleanInputs($this->Request);
			break;
		default:
			//$this->response('Bad Request',200);
			break;
		}
	}

	private function cleanInputs($data){
		$cleanInput = array();
		if(is_array($data)){
			foreach($data as $k => $v){
				$cleanInput[$k] = $this->cleanInputs($v);
			}
			return $cleanInput;
		}
		if(get_magic_quotes_gpc()){
			$data = trim(stripslashes($data));
		}
		$data = strip_tags($data);
		$cleanInput = trim($data);
		return $cleanInput;
	}


	private function setHeaders(){
		if(!headers_sent()){
			header("HTTP/1.1 ".$this->code." ".$this->getStatusMessage());
			header("Content-Type:".$this->ContentType."; charset=utf-8");
		}
	}

	public function parseIncomingParams() {
		$parameters = array();
		$parameters = array_merge($parameters,$this->parseGETParams());
		$parameters = array_merge($parameters,$this->parseParams());
		return $parameters;
	}
	
	private function parseGETParams() {
		$parameters = array();
		$queryString = $this->Environment->getServerVar('QUERY_STRING');
		parse_str($queryString, $parameters);
		return $parameters;
	}
	
	private function parseParams() {
		$parameters = array();
		$body = file_get_contents("php://input");
		if(!$body) {
			return $parameters;
		}
		$contentType = $this->Environment->getServerVar('CONTENT_TYPE');
		if(strpos($contentType, "application/json") !== false) {
			$bodyParams = json_decode($body);
			foreach((array) $bodyParams as $paramName => $paramValue) {
				$parameters[$paramName] = $paramValue;
			}
			$this->format = "json";
		} else if(strpos($contentType, "application/x-www-form-urlencoded") !== false) {
			parse_str($body, $postvars);
			foreach($postvars as $field => $value) {			
				$parameters[$field] = $value;
			}
			$this->format = "html";
		} 
		if(empty($parameters)) {
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
		$requestUri = $this->Environment->getServerVar('REQUEST_URI');
		if(empty($requestUri))
			$uri ="/";
		else if (strpos($requestUri, '?') !== false) 
		    $uri = substr($requestUri, 0, strpos($requestUri, '?'));
		else 
			$uri = $requestUri;
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
	
	function getHeaders()  { 
		$headers = ''; 
		foreach ($this->Environment->getServer() as $name => $value)  { 
			if (substr($name, 0, 5) == 'HTTP_') { 
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
			} 
		} 
		return $headers; 
	} 
}

if (!function_exists('apache_request_headers')) { 
	function apache_request_headers() { 
		foreach(Environment::getServer() as $key=>$value) { 
			$out[$key]=$value; 
			if (substr($key,0,5)=="HTTP_") { 
				$key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5))))); 
				$out[$key]=$value; 
			}
		} 
		return $out; 
	} 
}

?>
