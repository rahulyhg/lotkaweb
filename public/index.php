<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

class SJsonResponseProvider
{
    public function withOk($response, $data, $message = null)
    {
        return $response->withJson(['status' => 'success', 'data' => $data, 'message' => $message]);
    }
    
    public function withError($response, $message, $statusCode, $data = null)
    {
        return $response->withJson(['status' => 'error', 'data' => $data, 'message' => $message], $statusCode);
    }
}

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    $renderer_settings = $container->get('settings')['renderer'];

    $view = new \Slim\Views\Twig($renderer_settings['template_path'], [
        'cache' => $renderer_settings['cache_path'],
        'auto_reload' => true,
        'debug' => true,
    ]);
    

    $view->addExtension(new Twig_Extension_Debug());

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    return $view;
};

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
