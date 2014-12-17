<?php
require_once("CyberREST.php");

// CALL: http://localhost/API/Contact?name=Miguel&surname=Mendoza

class API extends CyberREST {
	
	public function processApi(){
		$parts = $this->getRequestPartsFrom("Contact");
		if($parts[0]=='Contact') {
			switch($this->getRequestMethod()) {
			case "GET":
				$this->getContact();
				break;
			default:
				$this->response('',404);
			}
		}
		else 
			$this->response('',404);
	}

	private function getContact() {
		$params = $this->parseIncomingParams();
		if(isset($params["name"]) && isset($params["surname"]) {
			$contact = array("name"=>"Miguel", "surname"=>"S. Mendoza");
			$this->response($this->encodeJSONforHTML($contact),200);
		}
		else
			$this->response('',404);
	}
}

$api = new API;
$api->processApi();

?>
