<?php
use PHPUnit\Framework\TestCase;
class CyberRESTTest extends TestCase
{
	public function getInstance() {
		$config = new CyberConfig("API", "blablabla", "CyberPruebas");
		return new CyberREST($config);
	}
	public function testGetMapping() {
		$_SERVER["REQUEST_METHOD"] = "GET";
		$_SERVER["REQUEST_URI"] = "http://www.foo.bar/API/test/foo/bar";
		$cyber = $this->getInstance();
		$this->assertEquals("GET", $cyber->getRequestMethod());
		$cyber->get("/test/{foo}/{bar}", function($app, $id, $nombre) {
			$this->assertEquals("foo", $id);
			$this->assertEquals("bar", $nombre);
		});
		$this->assertFalse($cyber->checkRefererWhiteList());
		$this->assertEquals("API", $cyber->getApiStart());
        $this->assertEquals(["test","foo","bar"],$cyber->getRequestParts());
        $this->assertNotNUll($cyber->getParameters());
        //$this->assertEquals(["test","{foo}","{bar}"],$cyber->parsePattern("/test/{foo}/{bar}"));
	}

	public function testClientIp() {
		$cyber = $this->getInstance();
		$_SERVER["HTTP_CLIENT_IP"] = "1.2.3.4";
		$this->assertEquals("1.2.3.4", $cyber->getClientIP());
		unset($_SERVER["HTTP_CLIENT_IP"]);
		$_SERVER["HTTP_X_FORWARDED_FOR"] = "4.3.2.1";
		$this->assertEquals("4.3.2.1", $cyber->getClientIP());
		unset($_SERVER["HTTP_X_FORWARDED_FOR"]);
		$_SERVER["REMOTE_ADDR"] = "1.1.1.1";
		$this->assertEquals("1.1.1.1", $cyber->getClientIP());
		unset($_SERVER["HTTP_X_FORWARDED_FOR"]);
	}

	public function testCanEncodeJSONforHTML() {
		$cyber = new CyberREST();
		$array = ["html"=>"<h1>Hólä</h1>"];
		$this->assertEquals('{"html":"&lt;h1&gt;H&oacute;l&auml;&lt;\/h1&gt;"}', $cyber->encodeJSONforHTML($array));
	}

	public function testCanAuthorizeRequest() {
		$cyber = $this->getInstance();
		$this->assertNotNUll($cyber->OAuth->createToken(["nombre"=>"Miguel"]));
		$_SERVER["authorization"] = $cyber->OAuth->createToken(["nombre"=>"Miguel"]);
		$this->expectException(Exception::class);
		$cyber->OAuth->authorizeRequest();
		unset($_SERVER["authorization"]);
		$_SERVER["Authorization"] = $cyber->OAuth->createToken(["nombre"=>"Miguel"]);
		$this->expectException(Exception::class);
		$cyber->OAuth->authorizeRequest();
	}
}