<?php

use Slim\Http\Request;
use Slim\Http\Response;

require_once __DIR__ . '/vendor/autoload.php';
session_start();

// dotenv
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/.env');

require 'src/utilities.php';
require 'src/twig-config.php';

// routes
foreach (glob("src/routes/*.php") as $filename) include $filename;

$app->get('{url:.*}/', function (Request $request, Response $response, array $args) {
    return $response->withRedirect($args["url"], 301);
});

// Run app
$app->run();
