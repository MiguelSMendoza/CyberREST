<?php
	
class CyberRESTTest extends \PHPUnit_Framework_TestCase
{
	public function testGetMapping() {
		$cyber = new CyberREST("TEST");
		$cyber->get("/test/{foo}/{bar}", function($app, $id, $nombre) {
			//NOTHING;
		});
		$this->assertFalse($cyber->verifyRequest());
		$this->assertFalse($cyber->checkReferer("foo"));
		$this->assertNull($cyber->handleTokenRequest());
        $this->assertNotNUll($cyber->getReferer());
        $this->assertEquals("TEST", $cyber->getApiStart());
        $this->assertNotNUll($cyber->getRequestParts());
        $this->assertNotNUll($cyber->getParameters());
        $this->assertEquals(["test","{foo}","{bar}"],$cyber->parsePattern("/test/{foo}/{bar}"));
	}
}