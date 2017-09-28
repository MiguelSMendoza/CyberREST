[![Build Status](https://travis-ci.org/MiguelSMendoza/CyberREST.svg?branch=master)](https://travis-ci.org/MiguelSMendoza/CyberREST) [![Test Coverage](https://codeclimate.com/github/MiguelSMendoza/CyberREST/badges/coverage.svg)](https://codeclimate.com/github/MiguelSMendoza/CyberREST/coverage)
# CyberREST
Helper Class to create a REST API simmilar to Silex
## Usage
```
http://www.domain.com/services/API/api.php/hola/23/Miguel
```
```
// api.php
$cyber = new CyberREST("API");
$cyber->get("/hola/{id}/{nombre}", function($app, $id, $nombre) {
	$app->response(json_encode(array($id, $nombre)), 200);
});
```