<?php
use PHPUnit\Framework\TestCase;
class CyberRESTTest extends TestCase
{
	public function testGetMapping() {
		$config = new CyberConfig("TEST", "blablabla", "CyberPruebas");
		$cyber = new CyberREST($config);
		$cyber->get("/test/{foo}/{bar}", function($app, $id, $nombre) {
			//NOTHING;
		});
		$this->assertFalse($cyber->checkRefererWhiteList());
		$this->assertNotNUll($cyber->createToken(["nombre"=>"Miguel"]));
        $this->assertNotNUll($cyber->getRequestMethod());
        $this->assertNotNUll($cyber->getClientIP());
        $this->assertEquals("TEST", $cyber->getApiStart());
        $this->assertNotNUll($cyber->getRequestParts());
        $this->assertNotNUll($cyber->getParameters());
        $this->assertEquals(["test","{foo}","{bar}"],$cyber->parsePattern("/test/{foo}/{bar}"));
	}

	public function testCanAuthorizeRequest() {
		$config = new CyberConfig("TEST", "blablabla", "CyberPruebas");
		$cyber = new CyberREST($config);
		$cyber->get("/test/{foo}/{bar}", function($app, $id, $nombre) {
			//NOTHING;
		});
		$this->expectException(Exception::class);
		$cyber->authorizeRequest();
	}
}