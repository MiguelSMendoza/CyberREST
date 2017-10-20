<?php
require_once(dirname(__FILE__).'/../vendor/autoload.php');

$app = new CyberREST("API");

$app->post("/test", function($app) {
    $params = $app->getParameters();
    $app->response($params,200);
});

$app->get("/test", function($app) {
    $params = $app->getParameters();
    $app->response($params,200);
});