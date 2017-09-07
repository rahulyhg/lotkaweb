<?php
// DIC configuration
use \Symfony\Component\HttpFoundation\Request;

//Stripe Setup
$stripe_settings = $container['settings']['stripe_live'];
\Stripe\Stripe::setApiKey($stripe_settings['SECRET_KEY']);
\Stripe\Stripe::setApiVersion($stripe_settings['API_VERSION']);

// Setup Eloquent
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// Define Twig View
$container['view'] = function($container) {
  $renderer_settings = $container->get('settings')['renderer'];

  $view = new \Slim\Views\Twig($renderer_settings['template_path'], [
      'cache' => $renderer_settings['cache_path'],
      'auto_reload' => $renderer_settings['auto_reload'],
      'debug' => $renderer_settings['debug'],
  ]);
  
  if($renderer_settings['debug']) {
    $view->addExtension(new Twig_Extension_Debug());
  }
  
  $view->addExtension(new \Slim\Views\TwigExtension(
    $container->router,
    $container->request->getUri()
  ));

  $view->getEnvironment()->addGlobal("current_path", 
    $container["request"]->getUri()->getPath()
  );

  $view->getEnvironment()->addFilter(new Twig_SimpleFilter(
      'isActive', 
      function ($paths) use ($container){
        $paths = is_array($paths) ? $paths : [$paths];
        return in_array($container["request"]->getUri()->getPath(), $paths) ? 'active open' : '';
      })
  );
  
  $view->getEnvironment()->addGlobal('auth', [
    'check' => $container->sentinel->check(),
    'user' => $container->sentinel->getUser(),
    'isAdmin' => $container->auth->isAdmin(),
    'getRoles' => $container->auth->roles()
  ]);

  $view->getEnvironment()->addGlobal('flash', $container->flash);

  return $view;
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
$container['db'] = function ($container) use ($capsule) {
    return $capsule;
};

// Flash Messages
$container['flash'] = function($container) {
  return new \Slim\Flash\Messages;
};

// CSRF Protection
$container['csrf'] = function($container) {
  return new \Slim\Csrf\Guard;
};

$container['hasher'] = function ($container) {
    return new Cartalyst\Sentinel\Hashing\BcryptHasher;
};

$container['dispatcher'] = function ($container) {
    return new Illuminate\Events\Dispatcher;
};

// Add Sentinel
$container['sentinel'] = function ($container) {
  $sentinel = (new \Cartalyst\Sentinel\Native\Facades\Sentinel())->getSentinel();
  $sentinel->setUserRepository(
    new \Cartalyst\Sentinel\Users\IlluminateUserRepository(
      $container['hasher'],
      $container['dispatcher'],
      App\Models\User::class // This is the proper model name for this case
    )
  );

  return $sentinel;
};
// Validator
$container['validator'] = function($container) {
  return new App\Validation\Validator;
};

$container['auth'] = function($container) {
  return new App\Auth\Auth($container);
};

/*
| CONTROLLERS
*/

$container[App\Tickets\Stripe::class] = function ($c) { return new \App\Tickets\Stripe($c); };
$container[App\API\Names::class]      = function ($c) { return new \App\API\Names($c); };
$container[App\Pages\OpenPage::class] = function ($c) { return new \App\Pages\OpenPage($c); };

$container['AuthController']          = function($c) { return new \App\Controllers\Auth\AuthController($c); };
$container['AdminController']         = function($c) { return new \App\Controllers\Admin\AdminController($c); };
$container['UserActionController']    = function($c) { return new \App\Controllers\Admin\UserActionController($c); };
$container['OrderActionController']   = function($c) { return new \App\Controllers\Admin\OrderActionController($c); };
