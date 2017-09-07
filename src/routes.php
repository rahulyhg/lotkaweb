<?php
use App\Middleware\AdminMiddleware;

/*
| OPEN PAGE ROUTES
*/

$app->get('/[pay/{sku}]', '\App\Pages\OpenPage:index')->setName('home');
$app->post('/charge', 'App\Pages\OpenPage:charge');

/*
| API ROUTES
*/

$app->group('/api/v1', function () {
  $this->get('/test', '\App\API\Names:test');
  
  $this->get('/names', function ($request, $response, $args) {
    $names = \App\API\Names::get();
    
    return $this
      ->json_provider
      ->withOk(
        $response, 
        ['surnames' => $names], 
        "Unreserved names as of: ". date("Y-m-d H:i:s")
    );
  });

  $this->get('/reserve/{name}', function ($request, $response, $args) {
    $name = filter_var($args['name'], FILTER_SANITIZE_STRING);
    $res = \App\API\Names::reserve($name);

    if ($res) {
      return $this->json_provider->withOk(
          $response, 
          ['surnames' => $name], 
          "Surname reserved on ". date("Y-m-d H:i:s")
        );
    } else {
      return $this->json_provider->withError(
          $response, 
          "Surname could not be reserved. It either does not exist or is not available.", 
          400, 
          ['surname' => $name]
        );
    }

  });

  $this->get('/release/{name}', function ($request, $response, $args) {
    $name = filter_var($args['name'], FILTER_SANITIZE_STRING);
    $res = \App\API\Names::release($name);

    if ($res) {
      return $this->json_provider->withOk(
        $response, 
        ['surnames' => $name], 
        "Surname released on ". date("Y-m-d H:i:s")
      );
    } else {
      return $this->json_provider->withError(
        $response, 
        "Surname could not be released. It either does not exist or is reserved via order.", 
        400, 
        ['surname' => $name]
      );
    }
  });
  
  $this->get('/external/texttalk', function ($request, $response, $args) {
    $texttalk_settings = $this->get('settings')['external_stores']['texttalk'];
    $choices = \App\API\Names::updateFromTextTalk($texttalk_settings);
    
    return $this->json_provider->withOk(
      $response, 
      ["surnames" => $choices], 
      "Names reserved by external provider TextTalk."
    );
  });
  
});

$app->group('/user', function() {
  
  $this->get('/register', 'AuthController:getRegister')->setName('user.register');
  $this->post('/register', 'AuthController:postRegister');

  $this->get('/login', 'AuthController:getLogin')->setName('user.login');
  $this->post('/login', 'AuthController:postLogin');

  $this->get('/logout', 'AuthController:logout')->setName('user.logout');
  
});

//Admin
$app->group('/admin', function() use ($container) {

  $event_settings = $container->get('settings')['event'];
    
  $container->get('view')->getEnvironment()->addGlobal('ticketData', [
    'totalAmount' => \App\Models\Order::query()->sum('amount'),
    'totalSold' => \App\Models\Order::query()->distinct('email')->count(),
    'target' => $event_settings['ticket']['target'],
    'goal' => $event_settings['ticket']['goal'],
    'daysLeft' => floor((strtotime($event_settings['date']) - time())/60/60/24),
  ]);
  
  $this->get('', 'AdminController:index')->setName('admin.index');

  //Users
  $this->group('/users', function() {
    
    $this->get('', 'UserActionController:index')->setName('admin.users.all');
    
    $this->get('/add', 'UserActionController:addUser')->setName('admin.user.add');
    $this->post('/add', 'UserActionController:postAddUser');
    
    $this->get('/{uid}/edit', 'UserActionController:editUser')->setName('admin.user.edit');
    $this->post('/{uid}/edit', 'UserActionController:postEditUser');

    $this->get('/{uid}/delete', 'UserActionController:deleteUser')->setName('admin.user.delete');
    
    $this->get('/create-from-order/{uid}', 'UserActionController:createFromOrderAndAttest')->setName('admin.order.create.user');    
  });
  
  //Orders
  $this->group('/orders', function() {
    
    $this->get('', 'OrderActionController:index')->setName('admin.orders.all');  

    $this->get('/attested', 'OrderActionController:listAttested')->setName('admin.orders.attested');
    $this->get('/unattested', 'OrderActionController:listUnattested')->setName('admin.orders.unattested');

    $this->get('/add', 'OrderActionController:addOrder')->setName('admin.order.add');
    $this->post('/add', 'OrderActionController:postAddOrder');

    //Handle external orders
    $this->group('/external', function() {
      $this->get('', 'OrderActionController:extrenalDashboard')->setName('admin.orders.external');
      
      $this->get('/stripe', 'OrderActionController:extrenalStripe')->setName('admin.orders.external.stripe');
      $this->post('/stripe', 'OrderActionController:postExtrenalStripe');
      
      $this->get('/texttalk', 'OrderActionController:extrenalTextTalk')->setName('admin.orders.external.texttalk');
      $this->post('/texttalk', 'OrderActionController:postExtrenalTextTalk');
    });
        
    //Handle specific orders
    $this->group('/{uid}', function() {
      $this->get('/edit', 'OrderActionController:editOrder')->setName('admin.order.edit');
      $this->post('/edit', 'OrderActionController:postEditOrder');

      $this->get('/delete', 'OrderActionController:deleteOrder')->setName('admin.order.delete');

      $this->get('/attest', 'OrderActionController:attestOrder')->setName('admin.order.attest');
      $this->post('/attest', 'OrderActionController:postAttestOrder');

      $this->get('/unattest', 'OrderActionController:unattestOrder')->setName('admin.order.unattest');
    });
  });
  
})->add(new AdminMiddleware($container));


$app->get('/setup', function () {
  $sentinel = $this->sentinel;
  
  if( !$sentinel->findRoleBySlug('admin') ) {
    $sentinel->getRoleRepository()->createModel()->create(array(
        'name'          => 'Admin',
        'slug'          => 'admin',
        'permissions'   => array(
            'user.create' => true,
            'user.update' => true,
            'user.delete' => true
        ),
    ));  
  }

  if( !$sentinel->findRoleBySlug('user') ) {
    $sentinel->getRoleRepository()->createModel()->create(array(
      'name'          => 'User',
      'slug'          => 'user',
      'permissions'   => array(
          'user.update' => true
      ),
    ));
  }

  if( !$sentinel->findRoleBySlug('participant') ) {
    $sentinel->getRoleRepository()->createModel()->create(array(
      'name'          => 'Participant',
      'slug'          => 'participant',
      'permissions'   => array(
          'user.update' => true
      ),
    ));
  }  
});