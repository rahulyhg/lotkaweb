<?php
use App\Middleware\AdminMiddleware;
use App\Middleware\ParticipantMiddleware;
use App\Middleware\WriterMiddleware;

/*
| API ROUTES
*/

//Global view values
$container->get('view')->getEnvironment()->addGlobal('site', [
    'siteName' => 'Lotka-Volterra | Sci-fi larp',
    'basePath' => '/',
    'navPath' => '/',
    'playerCount' => \App\Models\Order::distinct()->count(["email"]),
    'devBlog' => $container['HomePageController']->devPosts(),
  ]);  

$app->group('/api/v1', function () {
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

//User
$app->group('/user', function() {
  
  $this->get('/register', 'AuthController:getRegister')->setName('user.register');
  $this->post('/register', 'AuthController:postRegister');

  $this->get('/login', 'AuthController:getLogin')->setName('user.login');
  $this->post('/login', 'AuthController:postLogin');

  $this->get('/logout', 'AuthController:logout')->setName('user.logout');
  
  $this->post('/forgot', 'AuthController:postReminder')->setName('user.reminder');

  $this->get('/reset/{code}', 'AuthController:getResetPassword')->setName('user.reset');
  $this->post('/reset/{code}', 'AuthController:resetPassword');

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
  
  $auth = $container->get('view')->getEnvironment()->getGlobals()['auth'];
  if ($auth['user']) {
    $container->get('view')->getEnvironment()->addGlobal('userData', [
      'todos' => \App\Models\User::find($auth['user']->id)->tasks()->where('status', '<>', 1)->get(),
      'user' => $auth['user']
    ]);
  }

  $this->get('', 'AdminController:index')->setName('admin.index');

  //Todo
  $this->get('/todo', 'TaskActionController:todo')->setName('admin.tasks.todo');
  
  //Users
  $this->group('/users', function() {
    
    $this->get('', 'UserActionController:index')->setName('admin.users.all');
    
    $this->get('/add', 'UserActionController:addUser')->setName('admin.user.add');
    $this->post('/add', 'UserActionController:postAddUser');
    
    $this->get('/{uid}/edit', 'UserActionController:editUser')->setName('admin.user.edit');
    $this->post('/{uid}/edit', 'UserActionController:postEditUser');

    $this->get('/{uid}/delete', 'UserActionController:deleteUser')->setName('admin.user.delete');
    
    $this->get('/create-from-order/{uid}', 'UserActionController:createFromOrderAndAttest')->setName('admin.order.create.user'); 
    
    $this->get('/export', 'UserActionController:csv')->setName('admin.users.export'); 
    
    $this->get('/gallery', 'UserActionController:gallery')->setName('admin.users.gallery');    
  });
  
  //Orders
  $this->group('/orders', function() {
    
    $this->get('', 'OrderActionController:index')->setName('admin.orders.all');  

    $this->get('/attested', 'OrderActionController:listAttested')->setName('admin.orders.attested');
    $this->get('/unattested', 'OrderActionController:listUnattested')->setName('admin.orders.unattested');
    $this->get('/partial', 'OrderActionController:listPartialPayments')->setName('admin.orders.partial');
    $this->get('/multiples', 'OrderActionController:listMultiples')->setName('admin.orders.multiples');

    $this->get('/add', 'OrderActionController:addOrder')->setName('admin.order.add');
    $this->post('/add', 'OrderActionController:postAddOrder');
    
    $this->get('/export', 'OrderActionController:csv')->setName('admin.orders.export');     

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
  
  //Tickets
  $this->group('/tickets', function() {
    $this->get('', 'TicketActionController:index')->setName('admin.tickets.all');
    
    $this->get('/add', 'TicketActionController:add')->setName('admin.ticket.add');
    $this->post('/add', 'TicketActionController:postAdd');
    
    $this->get('/{uid}/edit', 'TicketActionController:edit')->setName('admin.ticket.edit');
    $this->post('/{uid}/edit', 'TicketActionController:postEdit');

    $this->get('/{uid}/delete', 'TicketActionController:delete')->setName('admin.ticket.delete');
  });
  
  //Tasks
  $this->group('/tasks', function() {
    $this->get('', 'TaskActionController:index')->setName('admin.tasks');
    $this->get('/all', 'TaskActionController:allTasks')->setName('admin.tasks.all');
    $this->get('/done', 'TaskActionController:doneTasks')->setName('admin.tasks.done');
    $this->get('/blocked', 'TaskActionController:blockedTasks')->setName('admin.tasks.blocked');
    
    $this->get('/add[/{uid}]', 'TaskActionController:add')->setName('admin.task.add');
    $this->post('/add', 'TaskActionController:postAdd');
    
    $this->get('/{uid}/edit', 'TaskActionController:edit')->setName('admin.task.edit');
    $this->post('/{uid}/edit', 'TaskActionController:postEdit');

    $this->get('/{uid}/delete', 'TaskActionController:delete')->setName('admin.task.delete');
    
    $this->get('/{uid}/start', 'TaskActionController:startTask')->setName('admin.task.start');    
    $this->get('/{uid}/complete', 'TaskActionController:completeTask')->setName('admin.task.complete');    
    $this->get('/{uid}/blocked', 'TaskActionController:blockedTask')->setName('admin.task.blocked');    
  });
  
  //Posts
  $this->group('/post', function() {
    $this->get('', 'PostActionController:index')->setName('admin.posts.all');
    
    $this->get('/add', 'PostActionController:add')->setName('admin.post.add');
    $this->post('/add', 'PostActionController:postAdd');
    
    $this->get('/{uid}/edit', 'PostActionController:edit')->setName('admin.post.edit');
    $this->post('/{uid}/edit', 'PostActionController:postEdit');

    $this->get('/{uid}/publish', 'PostActionController:publish')->setName('admin.post.publish');
    $this->get('/{uid}/unpublish', 'PostActionController:unpublish')->setName('admin.post.unpublish');

    $this->get('/{uid}/delete', 'PostActionController:delete')->setName('admin.post.delete');
  });
  
  //Participants
  $this->group('/participants', function() {
    //TODO: Fix participant dashboard (for writers)
    $this->get('','AdminController:index')->setName('admin.participant.dashboard');
    
    //Characters
    $this->group('/characters', function () {
      $this->get('/all', 'CharacterActionController:index')->setName('admin.character.list');
      $this->get('/submitted', 'CharacterActionController:submitted_for_review')->setName('admin.character.list.submitted');
      $this->get('/reviewed', 'CharacterActionController:reviewed')->setName('admin.character.list.reviewed');
      
      $this->get('/add', 'CharacterActionController:add')->setName('admin.character.add');
      $this->post('/add', 'CharacterActionController:post');

      $this->get('/generate', 'CharacterActionController:generate')->setName('admin.character.generate');
      $this->post('/generate', 'CharacterActionController:postGenerate');
      
      $this->get('/{uid}/edit', 'CharacterActionController:edit')->setName('admin.character.edit');
      $this->post('/{uid}/edit', 'CharacterActionController:post');
      
      $this->get('/{uid}/delete', 'CharacterActionController:delete')->setName('admin.character.delete');
    });

    //Groups
    $this->group('/groups', function () {
      $this->get('/all', 'GroupActionController:index')->setName('admin.group.list');
      
      $this->get('/add', 'GroupActionController:add')->setName('admin.group.add');
      $this->post('/add', 'GroupActionController:post');
      
      $this->get('/{uid}/edit', 'GroupActionController:edit')->setName('admin.group.edit');
      $this->post('/{uid}/edit', 'GroupActionController:post');
      
      $this->get('/{uid}/delete', 'GroupActionController:delete')->setName('admin.group.delete');
    });
    
    //Plots
    $this->group('/plots', function () {
      $this->get('/all', 'PlotActionController:index')->setName('admin.plot.list');
      
      $this->get('/add', 'PlotActionController:add')->setName('admin.plot.add');
      $this->post('/add', 'PlotActionController:post');
      
      $this->get('/{uid}/edit', 'PlotActionController:edit')->setName('admin.plot.edit');
      $this->post('/{uid}/edit', 'PlotActionController:post');
      
      $this->get('/{uid}/delete', 'PlotActionController:delete')->setName('admin.plot.delete');
    });
    
    //Relation
    $this->group('/relations', function () {
      $this->get('/all', 'RelationActionController:index')->setName('admin.relation.list');
      
      $this->get('/add', 'RelationActionController:add')->setName('admin.relation.add');
      $this->post('/add', 'RelationActionController:post');

      $this->get('/generate', 'RelationActionController:generate')->setName('admin.relation.generate');
      $this->post('/generate', 'RelationActionController:postGenerate');
      
      $this->get('/{uid}/edit', 'RelationActionController:edit')->setName('admin.relation.edit');
      $this->post('/{uid}/edit', 'RelationActionController:post');
      
      $this->get('/{uid}/delete', 'RelationActionController:delete')->setName('admin.relation.delete');
    });
  });
  
  //Media
  $this->group('/media', function() {
    $this->get('', 'MediaActionController:index')->setName('admin.media.all');
    
    $this->get('/add', 'MediaActionController:add')->setName('admin.media.add');
    $this->post('/add', 'MediaActionController:postAdd');
    
    $this->get('/portraits', 'MediaActionController:portraits')->setName('admin.media.portraits');
    
    $this->get('/{uid}/edit', 'MediaActionController:edit')->setName('admin.media.edit');
    $this->post('/{uid}/edit', 'MediaActionController:postEdit');

    $this->get('/{uid}/delete', 'MediaActionController:delete')->setName('admin.media.delete');
  });  
  
  //Bulk mail
  $this->group('/email', function() {
    $this->get('', 'BulkmailActionController:compose')->setName('admin.bulkmail');    
    $this->post('', 'BulkmailActionController:send');
  });
  
  //Attributes
  $this->group('/attributes', function () {
    $this->get('/all', 'AttributeActionController:index')->setName('admin.attributes.list');

    $this->get('/add', 'AttributeActionController:add')->setName('admin.attributes.add');
    $this->post('/add', 'AttributeActionController:post');

    $this->get('/{uid}/edit', 'AttributeActionController:edit')->setName('admin.attributes.edit');
    $this->post('/{uid}/edit', 'AttributeActionController:post');

    $this->get('/{uid}/delete', 'AttributeActionController:delete')->setName('admin.attributes.delete');
  });  
  
  //Lists
  $this->group('/lists', function () {
    $this->get('/all', 'ListActionController:index')->setName('admin.lists.list');

    $this->get('/add', 'ListActionController:add')->setName('admin.lists.add');
    $this->post('/add', 'ListActionController:post');

    $this->get('/{uid}/edit', 'ListActionController:edit')->setName('admin.lists.edit');
    $this->post('/{uid}/edit', 'ListActionController:save');

    $this->get('/{uid}/delete', 'ListActionController:delete')->setName('admin.lists.delete');
  });
  
  //Items
  $this->group('/items', function () {
    $this->get('/all', 'ListActionController:items')->setName('admin.items.list');

    $this->get('/add', 'ListActionController:addItem')->setName('admin.items.add');
    $this->post('/add', 'ListActionController:saveItem');

    $this->get('/{uid}/edit', 'ListActionController:editItem')->setName('admin.items.edit');
    $this->post('/{uid}/edit', 'ListActionController:saveItem');

    $this->get('/{uid}/delete', 'ListActionController:deleteItem')->setName('admin.items.delete');
    
    $this->get('/taxon/{name}[/{parent}]', 'ListActionController:addUpdateTaxons')->setName('admin.items.taxon');
    
  });    
  
})->add(new AdminMiddleware($container));

//Setup
/*
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
*/

/*
| PARTICIPANT PAGE ROUTES
*/

$app->group('/welcome/', function () { 
  $this->get('{hash}/[{stage}]', 'OnboardingPageController:onboarding')
    ->setName('participant.onboarding'); 
  $this->post('{hash}/[{stage}]', 'OnboardingPageController:save'); 
});

$app->group('/participants', function () use ($container) { 
  
  $auth = $container->get('view')->getEnvironment()->getGlobals()['auth'];
  
  if ($auth['user']) {
    $user_model = \App\Models\User::find($auth['user']->id);
    $container->get('view')->getEnvironment()->addGlobal('userData', [
      'todos' => $user_model->tasks()->where('status', '<>', 1)->get(),
      'notifications' => $user_model->notifications()->where('seen_at', null)->get(),
      'user' => $auth['user'],
      'character' => \App\Models\Character::where('user_id', $auth['user']->id)->first(),
    ]);
  }
  
  $this->get('[/]', 'ParticipantPageController:index')->setName('participant.home');
  
  $this->get('/notify', 'NotificationActionController:addNotificationToCurrent');
  
  //Characters
  function characterRouring($t) {
    $t->get('[/]',       'CharacterPageController:index')
      ->setName('participant.character.list');
    
    $t->get('/my/{uid}', 'CharacterPageController:my')      
      ->setName('participant.character.my.admin');
    $t->post('/my/{uid}','CharacterPageController:save');
    
    $t->get('/my',       'CharacterPageController:my')
      ->setName('participant.character.my');      
    $t->post('/my',       'CharacterPageController:save');
    
    $t->get('/gallery',  'CharacterPageController:gallery')
      ->setName('participant.character.gallery');
    
    $t->get('/{uid}[/]', 'CharacterPageController:character')
      ->setName('participant.character');
  }
  
  $this->group('/characters', function() { characterRouring($this); });
  $this->group('/character', function() { characterRouring($this); });
  
  //Players
  function playerRouring($t) {
    $t->get('[/]',       'PlayersPageController:index')
      ->setName('participant.player.list');
    $t->get('/gallery',  'PlayersPageController:gallery')
      ->setName('participant.player.gallery');
    $t->get('/{uid}[/]', 'PlayersPageController:player')
      ->setName('participant.player');
  }
  
  $this->group('/players', function() { playerRouring($this); });
  $this->group('/player', function() { playerRouring($this); });
  
  //Groups
  function groupRouring($t) {
    $t->get('[/]',         'GroupPageController:index')
      ->setName('participant.group.list');

    $t->get('/my[/{uid}]', 'GroupPageController:my')
      ->setName('participant.group.my');

    $t->get('/add',        'GroupPageController:add')
      ->setName('participant.group.add');
    $t->post('/add',       'GroupPageController:post');

    $t->get('/{uid}/edit', 'GroupPageController:edit')
      ->setName('participant.group.edit');
    $t->post('/{uid}/edit','GroupPageController:post');

    $t->get('/{uid}/delete','GroupPageController:delete')
      ->setName('participant.group.delete');

    $t->get('/{uid}/join', 'GroupPageController:join')
      ->setName('participant.group.request');
    $t->post('/{uid}/join','GroupPageController:postJoin');

    $t->get('/{uid}[/]',   'GroupPageController:group')
      ->setName('participant.group');
  }
  
  $this->group('/groups', function() { groupRouring($this); });
  $this->group('/group', function() { groupRouring($this); });
  
  //Plots
  function plotRouting($t) {
    $t->get('[/]', 'PlotPageController:index')
      ->setName('participant.plot.list');

    $t->get('/my[/{uid}]', 'PlotPageController:my')
      ->setName('participant.plot.my');

    $t->get('/add', 'PlotPageController:add')
      ->setName('participant.plot.add');
    $t->post('/add', 'PlotPageController:post');

    $t->get('/{uid}/edit', 'PlotPageController:edit')
      ->setName('participant.plot.edit');
    $t->post('/{uid}/edit', 'PlotPageController:post');

    $t->get('/{uid}/delete', 'PlotPageController:delete')
      ->setName('participant.plot.delete');

    $t->get('/{uid}/join', 'PlotPageController:join')
      ->setName('participant.plot.request');
    $t->post('/{uid}/join', 'PlotPageController:postJoin');

    $t->get('/{uid}[/]', 'PlotPageController:plot')
      ->setName('participant.plot');    
  }
               
  $this->group('/plots', function() { plotRouting($this); });
  $this->group('/plot', function() { plotRouting($this); });

  //Relations
  function relationsRouting($t) {
    $t->get('[/]', 'RelationPageController:index')
      ->setName('participant.relation.list');

    $t->get('/requests', 'RelationPageController:publicRequests')
      ->setName('participant.relation.requests');    
    
    $t->get('/pending/{uid}', 'RelationPageController:pending')
      ->setName('participant.relation.pending.admin');
    
    $t->get('/pending', 'RelationPageController:pending')
      ->setName('participant.relation.pending');

    $t->get('/my/{uid}', 'RelationPageController:my')      
      ->setName('participant.relation.my.admin');
    
    $t->get('/my', 'RelationPageController:my')
      ->setName('participant.relation.my');      
        
    $t->get('/add', 'RelationPageController:add')
      ->setName('participant.relation.add');
    $t->post('/add', 'RelationPageController:post');
    
    $t->post('/{uid}/edit', 'RelationPageController:edit')->setName('participant.relation.edit');

    $t->get('/{uid}/delete', 'RelationPageController:delete')
      ->setName('participant.relation.delete');

    $t->get('/{uid}/accept', 'RelationPageController:accept')
      ->setName('participant.relation.accept');
    $t->get('/{uid}/reject', 'RelationPageController:reject')
      ->setName('participant.relation.reject');

    $t->get('/{uid}', 'RelationPageController:relation')
      ->setName('participant.relation');
  }
               
  $this->group('/relations', function() { relationsRouting($this); });
  $this->group('/relation', function() { relationsRouting($this); });
               
  // Schedules
  function scheduleRouring($t) {
    $t->get('[/]',        'SchedulePageController:index')
      ->setName('participant.schedules');
    $t->get('/my[/{uid}]','SchedulePageController:my')
      ->setName('participant.schedules.my');      
  }
               
  $this->group('/schedules', function() { scheduleRouring($this); });
  $this->group('/schedule', function() { scheduleRouring($this); });
  
  //Lists  
  $this->group('/list', function () {
    $this->get('/my', 'ListPageController:my')->setName('participant.list.my');
    $this->post('/my', 'ListPageController:save');
    
    $this->get('/pnqs', 'ListPageController:pnqs')->setName('participant.list.pnqs');
    $this->get('/ta', 'ListPageController:ta')->setName('participant.list.ta');

    $this->get('/item/{uid}', 'ListPageController:item')->setName('participant.list.item');
  });
  
  $this->get('/{page}[/{uid}]', 'ParticipantPageController:page')
    ->setName('participant.page');  
               
})->add(new ParticipantMiddleware($container));

/*
| OPEN PAGE ROUTES
*/

$app->group('/', function () use ($container) {
  $this->get('', 'HomePageController:index')->setName('home');              
  $this->get('press', 'HomePageController:press')->setName('page.press');  
  $this->get('ticket/{sku}', 'HomePageController:ticket')->setName('single.ticket');
  $this->post('charge', 'App\Pages\OpenPage:charge');  
  // $this->get('bulk', 'MediaActionController:bulk'); /* utility function - only for dev. env. use  */
  $this->get('{category}[/{page}]', 'HomePageController:page')->setName('open.page'); //FINAL CATCH ALL
});
