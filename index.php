<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();

// dotenv
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/.env');

require __DIR__ . '/src/utilities.php';

// Create and configure Slim app
$app = new \Slim\App(['settings' => [
    'addContentLengthHeader' => false,
    'displayErrorDetails' => true,
]]);

// Get container
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/src/templates');
    $twig = new \Twig\Environment($loader, [
        'cache' => false,
        // 'cache' => __DIR__ . '/src/templates/cache',
    ]);

    $twig->addGlobal('current_user', (empty($_SESSION['current_user']) ? null : $_SESSION['current_user']));

    $twig->addGlobal('session_alert', (empty($_SESSION['session_alert']) ? null : $_SESSION['session_alert']));
    $_SESSION['session_alert'] = null;

    return $twig;
};

//Override the default Not Found Handler
$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $c['response']
            ->withStatus(404)
            ->write($c->view->render('error.html.twig', ['message' => '404 - Page introuvable']));
    };
};
$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        return $c['response']
            ->withStatus(500)
            ->write($c->view->render('error.html.twig', [
                'message' => $exception->getMessage(),
                "details" => $c['settings']['displayErrorDetails'] ? $exception->getFile() . ":" . $exception->getLine() : '',
            ]));
    };
};

require 'src/routes/signin.php';
require 'src/routes/settings.php';
require 'src/routes/views.php';

$app->get('{url:.*}/', function ($request, $response, $args) {
    return $response->withRedirect($args["url"], 301);
});

// Run app
$app->run();