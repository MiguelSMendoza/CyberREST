<?php
require_once("CyberREST.php");

// CALL: http://www.domain.com/services/API/hola/23/Miguel
$cyber = new CyberREST("API");
$cyber->get("/hola/{id}/{nombre}", function($app, $id, $nombre) {
	$app->response(json_encode(array($id, $nombre)), 200);
});

?>
