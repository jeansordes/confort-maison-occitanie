<?php

// Create and configure Slim app
$app = new \Slim\App(['settings' => [
    'addContentLengthHeader' => false,
    'displayErrorDetails' => true,
]]);

// Get container
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
    $twig = new \Twig\Environment($loader, [
        'cache' => false,
        // 'cache' => __DIR__ . '/templates/cache',
    ]);

    $twig->addGlobal('current_url', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)); // https://stackoverflow.com/a/25944383/5736301
    $twig->addGlobal('current_user', (empty($_SESSION['current_user']) ? null : $_SESSION['current_user']));
    $twig->addGlobal('is_admin', !empty($_SESSION['current_user']) && $_SESSION['current_user']['user_role'] == 'admin');
    $twig->addGlobal('is_localhost', in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1','::1']));
    $twig->addGlobal('is_dev_mode', !empty($_ENV['app_mode']) && $_ENV['app_mode'] == 'dev');

    $twig->addGlobal('session_alert', (empty($_SESSION['session_alert']) ? null : $_SESSION['session_alert']));
    $_SESSION['session_alert'] = null;

    $fixed_size_nbr = new \Twig\TwigFilter('fixed_size_nbr', function ($number, $digits_amount = 4) {
        return is_numeric($digits_amount) && is_numeric($number) ? sprintf('%0' . $digits_amount . 'd', $number) : $number;
    });
    $twig->addFilter($fixed_size_nbr);
    $filter = new \Twig\TwigFilter('timeago', function ($datetime) {
        $time = time() - strtotime($datetime);

        $units = array(
            31536000 => 'an',
            2592000 => 'mois',
            604800 => 'semaine',
            86400 => 'jour',
            3600 => 'heure',
            60 => 'minute',
            1 => 'seconde'
        );

        foreach ($units as $unit => $val) {
            if ($time < $unit) continue;
            $number_of_units = floor($time / $unit);
            return ($unit <= 1) ? "à l'instant" :
                'il y a ' . $number_of_units . ' ' . $val . (($number_of_units > 1 and $val != 'mois') ? 's' : '');
        }
    });
    $twig->addFilter($filter);
    
    $twig->addExtension(new \Twig\Extension\StringLoaderExtension());

    return $twig;
};

$container['upload_directory'] = realpath(__DIR__ . '/../uploads');

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
        console_log($exception);
        return $c['response']
            ->withStatus(500)
            ->write($c->view->render('error.html.twig', [
                'message' => $exception->getMessage(),
                "details" => $c['settings']['displayErrorDetails'] ? $exception->getFile() . ":" . $exception->getLine() : '',
            ]));
    };
};
