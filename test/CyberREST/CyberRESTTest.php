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
		$_SERVER["REQUEST_METHOD"] = "POST";
		$cyber->post("/test/{foo}/{bar}", function($app, $id, $nombre) {
			$this->assertEquals("foo", $id);
			$this->assertEquals("bar", $nombre);
		});
		$_SERVER["REQUEST_METHOD"] = "PUT";
		$cyber->put("/test/{foo}/{bar}", function($app, $id, $nombre) {
			$this->assertEquals("foo", $id);
			$this->assertEquals("bar", $nombre);
		});
		$_SERVER["REQUEST_METHOD"] = "DELETE";
		$cyber->delete("/test/{foo}/{bar}", function($app, $id, $nombre) {
			$this->assertEquals("foo", $id);
			$this->assertEquals("bar", $nombre);
		});
		$this->assertEquals(["test","{foo}","{bar}"],$cyber->getPatternParts());
	}

	public function testVariables() {
		$_SERVER["REQUEST_URI"] = "http://www.foo.bar/API/test/foo/bar";
		$cyber = $this->getInstance();
		$this->assertFalse($cyber->checkRefererWhiteList());
		$this->assertEquals("API", $cyber->getApiStart());
		$this->assertEquals(["test","foo","bar"],$cyber->getRequestParts());
	}

	public function testGETParameters() {
		$cyber = $this->getInstance();
		$client = new GuzzleHttp\Client(['base_uri' => 'http://localhost:1349']);
		$response = $client->get('http://localhost:1349/API/test?foo=bar&faa=bar');
		$this->assertEquals(["foo"=>"bar", "faa"=>"bar"], json_decode($response->getBody(true), true));
	}

	public function testPOSTParameters() {
		$cyber = $this->getInstance();
		$client = new GuzzleHttp\Client(['base_uri' => 'http://localhost:1349']);
		$data = array(
			'form_params' => array(
				'foo' => 'bar',
				'num' => 5
			)
		);
		$response = $client->post('http://localhost:1349/API/test', $data);
		$this->assertEquals(["foo"=>"bar", "num"=>5], json_decode($response->getBody(true), true));
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
		unset($_SERVER["authorization"]);
		$cyber = $this->getInstance();
		$this->assertNotNUll($cyber->OAuth->createToken(["nombre"=>"Miguel"]));
		$_SERVER["Authorization"] = 'Bearer ' . $cyber->OAuth->createToken(["nombre"=>"Miguel"]);
		$this->assertNotNUll($cyber->OAuth->authorizeRequest());
		// ...
		unset($_SERVER["Authorization"]);
		$cyber = $this->getInstance();
		$this->assertNotNUll($cyber->OAuth->createToken(["nombre"=>"Miguel"]));
		$_SERVER["authorization"] = 'Bearer ' . $cyber->OAuth->createToken(["nombre"=>"Miguel"]);
		$this->assertNotNUll($cyber->OAuth->authorizeRequest());
	}

	public function testCanUnAuthorizeRequest() {
		$cyber = $this->getInstance();
		unset($_SERVER["Authorization"]);
		unset($_SERVER["authorization"]);
		$this->expectException(NotAuthorizedException::class);
		$cyber->OAuth->authorizeRequest();
	}

	public function testCanUnAuthorizeWrongToken() {
		$cyber = $this->getInstance();
		unset($_SERVER["Authorization"]);
		unset($_SERVER["authorization"]);
		$_SERVER["authorization"] = 'Bearer A.B.C';
		$this->expectException(Exception::class);
		$cyber->OAuth->authorizeRequest();
	}

	public function testResponse() {
		$cyber = $this->getInstance();
		ob_start();
		$cyber->response(["Foo"=>"bar"], 200);
		$response = ob_get_clean();
		$this->assertEquals('{"Foo":"bar"}', $response);
		ob_start();
		$cyber->response(437, 200);
		$response = ob_get_clean();
		$this->assertEquals(437, $response);
	}
}