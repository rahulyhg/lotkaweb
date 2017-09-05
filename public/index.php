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

// USE 3RD PARTY
use Respect\Validation\Validator as v;

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

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// ADD CSRF
$app->add($container->csrf);

// ADD 3RD PARTY
v::with('App\\Validation\\Rules\\');

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
