<?php
// DIC configuration
use \Symfony\Component\HttpFoundation\Request;

//Stripe Setup
$stripe_settings = $container['settings']['stripe_live'];
\Stripe\Stripe::setApiKey($stripe_settings['SECRET_KEY']);
\Stripe\Stripe::setApiVersion($stripe_settings['API_VERSION']);
$container['stripe'] = $stripe_settings;


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

  $view->addExtension(new Twig_Extension_StringLoader());
  
  $view->getEnvironment()->addGlobal("current_path", 
    $container["request"]->getUri()->getPath()
  );
  
  $view->getEnvironment()->addFilter(new Twig_SimpleFilter(
      'key', 
      function ($collection, $key) {
        $a = [];
        foreach ($collection as $name => $value) {
          $a[$value->name] = $value->value; 
        }        
        return isset($a[$key]) ? $a[$key] : null;
      })
  );

  $view->getEnvironment()->addFilter(new Twig_SimpleFilter(
      'isActive', 
      function ($paths) use ($container){
        $paths = is_array($paths) ? $paths : [$paths];
        return in_array($container["request"]->getUri()->getPath(), $paths) ? 'active open' : '';
      })
  );
  
  $view->getEnvironment()->addFilter(new Twig_SimpleFilter(
      'editable', 
      function ($region) use ($container){
        if(!$container->auth->isAdmin()) return;        
        return " data-editable data-name=\"{$region}\" data-path=\"{$container["request"]->getUri()->getPath()}\"";
      })
  );
  
  $view->getEnvironment()->addGlobal('auth', [
    'check' => $container->sentinel->check(),
    'user' => $container->sentinel->getUser(),
    'isAdmin' => $container->auth->isAdmin(),
    'isParticipant' => $container->auth->isParticipant(),
    'getRoles' => $container->auth->roles(),
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

$container['reminders'] = function ($c) { 
  return new Cartalyst\Sentinel\Reminders\IlluminateReminderRepository($c->sentinel->getUserRepository()); 
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

$container[App\Tickets\Stripe::class]   = function ($c) { return new \App\Tickets\Stripe($c); };
$container[App\API\Names::class]        = function ($c) { return new \App\API\Names($c); };
$container[App\Pages\OpenPage::class]   = function ($c) { return new \App\Pages\OpenPage($c); };

$container['HomePageController']        = function($c) { return new \App\Controllers\Page\HomePageController($c); };

// Admin
$container['AuthController']            = function($c) { return new \App\Controllers\Auth\AuthController($c); };
$container['AdminController']           = function($c) { return new \App\Controllers\Admin\AdminController($c); };
$container['UserActionController']      = function($c) { return new \App\Controllers\Admin\UserActionController($c); };
$container['OrderActionController']     = function($c) { return new \App\Controllers\Admin\OrderActionController($c); };
$container['MediaActionController']     = function($c) { return new \App\Controllers\Admin\MediaActionController($c); };
$container['BulkmailActionController']  = function($c) { return new \App\Controllers\Admin\BulkmailActionController($c); };

// Participant admin
$container['RolesActionController']     = function($c) { return new \App\Controllers\Admin\RolesActionController($c); };
$container['ShirtActionController']     = function($c) { return new \App\Controllers\Admin\ShirtActionController($c); };
$container['SurnameActionController']   = function($c) { return new \App\Controllers\Admin\SurnameActionController($c); };
$container['TeamActionController']      = function($c) { return new \App\Controllers\Admin\TeamActionController($c); };
$container['TicketActionController']    = function($c) { return new \App\Controllers\Admin\TicketActionController($c); };
$container['TaskActionController']      = function($c) { return new \App\Controllers\Admin\TaskActionController($c); };
$container['PostActionController']      = function($c) { return new \App\Controllers\Admin\PostActionController($c); };
$container['CharacterActionController'] = function($c) { return new \App\Controllers\Admin\Participants\CharacterActionController($c); };
$container['GroupActionController']     = function($c) { return new \App\Controllers\Admin\Participants\GroupActionController($c); };
$container['PlotActionController']      = function($c) { return new \App\Controllers\Admin\Participants\PlotActionController($c); };
$container['RelationActionController']  = function($c) { return new \App\Controllers\Admin\Participants\RelationActionController($c); };

// Participant pages
$container['ParticipantPageController'] = function($c) { return new \App\Controllers\Page\Participant\ParticipantPageController($c); };
$container['OnboardingPageController']  = function($c) { return new \App\Controllers\Page\Participant\OnboardingPageController($c); };
$container['PlayersPageController']     = function($c) { return new \App\Controllers\Page\Participant\PlayersPageController($c); };
$container['GroupPageController']       = function($c) { return new \App\Controllers\Page\Participant\GroupPageController($c); };
$container['PlotPageController']        = function($c) { return new \App\Controllers\Page\Participant\PlotPageController($c); };
$container['RelationPageController']    = function($c) { return new \App\Controllers\Page\Participant\RelationPageController($c); };
$container['CharacterPageController']   = function($c) { return new \App\Controllers\Page\Participant\CharacterPageController($c); };
$container['SchedulePageController']    = function($c) { return new \App\Controllers\Page\Participant\SchedulePageController($c); };

