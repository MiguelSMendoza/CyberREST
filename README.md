[![Build Status](https://travis-ci.org/MiguelSMendoza/CyberREST.svg?branch=master)](https://travis-ci.org/MiguelSMendoza/CyberREST) [![Code Climate](https://codeclimate.com/github/MiguelSMendoza/CyberREST/badges/gpa.svg)](https://codeclimate.com/github/MiguelSMendoza/CyberREST) [![Test Coverage](https://codeclimate.com/github/MiguelSMendoza/CyberREST/badges/coverage.svg)](https://codeclimate.com/github/MiguelSMendoza/CyberREST/coverage) [![Issue Count](https://codeclimate.com/github/MiguelSMendoza/CyberREST/badges/issue_count.svg)](https://codeclimate.com/github/MiguelSMendoza/CyberREST)
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