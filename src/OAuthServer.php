<?php

class OAuthServer {
	private $dsn      = 'mysql:dbname=oauth;host=localhost';
	private $username = 'myquael';
	private $password = 'merlose';
	private $server;
	private $storage;
	
	public function getServer() {
		return $this->server;
	}
	
	public function getStorage() {
		return $this->storage;
	}

	public function __construct() {
		// error reporting (this is a demo, after all!)
		ini_set('display_errors',1);error_reporting(E_ALL);
		OAuth2\Autoloader::register();
		$this->storage = new OAuth2\Storage\Pdo(array('dsn' => $this->dsn, 'username' => $this->username, 'password' => $this->password));
		$this->server = new OAuth2\Server($this->storage);
		$this->server->addGrantType(new OAuth2\GrantType\ClientCredentials($this->storage));
		$this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->storage));
	}
	
	public function verifyResourceRequest($param) {
		return $this->server->verifyResourceRequest($param);
	}
	
	public function getResponse() {
		return $this->server->getResponse();
	}
	
	public function handleTokenRequest() {
		return $this->server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
	}
	
}	
	
	
