<?php
// DIC configuration

$container = $app->getContainer();
$container['app'] = function ($c) {
   global $app;
   return $app;
};

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// Service factory for the ORM
$container['db'] = function ($container) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($container['settings']['db']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    return $capsule;
};

/*
| CONTROLLERS
*/

$container[App\Tickets\Stripe::class] = function ($c) { return new \App\Tickets\Stripe($c); };
$container[App\API\Names::class]      = function ($c) { return new \App\API\Names($c); };
$container[App\Pages\OpenPage::class] = function ($c) { return new \App\Pages\OpenPage($c); };
