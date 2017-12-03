<?php

namespace App\Controllers\Admin;

use App\Models\User;
use App\Models\Roles;
use App\Models\Order;
use App\Models\Group;
use App\Models\Attribute;
use App\Models\Character;
use App\Models\Task;
use App\Models\Post;

use App\Controllers\Controller;

use App\Mail\Sender;
use App\Mail\Templater;

use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;


class UserActionController extends Controller
{
  
  private function sendEmail($recipient, $subject, $body, $vars) {
    $mail = new Sender($this->container->get('settings'));
    return $mail->send($recipient, $subject, $body, $vars);
  }  
  
  private function user_attributes() {
    return [
      'onboarding_complete',
      'gender',
      'id_number_swe', #(inte relevant för ej svenska medborgare)
      'birth_date',
      'membership_fee', #(behöver inte synas, men skall finnas med dolt med värdet 50kr)
      'membership_date', #(kan finnas med dolt med datum 20180101)
      'care_of', #(ej tvingande)
      'street_address_1',
      'street_address_2',
      'postal_code',
      'state',
      'city',
      'country',
      'phone',
      'emergency_contact',
      'emergency_phone',
      'allergies',
      'medical_conditions',
    ];
  }
  
  private function userOptions() {
    return [
      'character' => Character::orderBy('name')->get(),
      'groups' => Group::orderBy('name')->get(),
      'set_attr' => self::user_attributes(),
      'genders' => ['Non-binary','Female','Male','Other'],
    ];
  }  
  
  private function handlePostData($request) {
    $attributes = [ 
      'keys' => $request->getParam('attrKey'), 
      'values' => $request->getParam('attrVal')
    ];
    
    foreach (self::user_attributes() as $attr) {
      if ( strlen($request->getParam($attr)) ) {
        $attributes['keys'][] = $attr;
        $attributes['values'][] = $request->getParam($attr);
      }
    }
    
    $attribute_ids = [];
    
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
    }
    
    $groups = $request->getParam('group_ids');
    $groups = is_array($groups) ? $groups : [$groups];

    return [ 
      'attributes' => $attribute_ids,
      'groups' => $groups,
    ];
  }
  
  public function index($request, $response, $arguments)
  {
    $users = User::all();

    $roles = [];
    foreach ($users as $user) {
      $roles[] = $this->container->sentinel->findById($user->id)->roles()->get()->first();
    }
    
    return $this->view->render($response, 'admin/user/list.html', [
      'listUsers' => $users,
      'getUsersRole' => $roles,
    ]);
  }
  
  public function csv($request, $response, $arguments)
  {
    $users = User::all();
    $userList = [];
    foreach ($users as $user) {
      $userList[] = [
        "data" => $user,
        "order" => Order::where('user_id', $user->id)->first(),
        "attr" => self::mapAttributes( $user->attr )
      ];
    }
    
    return $this->view->render($response, 'admin/user/csv.html', [
      'listUsers' => $userList
    ])->withHeader('Content-Type', 'text/csv');
  }
  
  public function gallery($request, $response, $arguments)
  {
    $users = User::all();
    $userList = [];
    foreach ($users as $user) {
      $userList[] = [
        "data" => $user,
        "attr" => self::mapAttributes( $user->attr )
      ];
    }

    return $this->view->render($response, 'admin/user/gallery.html', [
      'users' => $userList,
    ]);
  }  
  
  public function deleteUser($request, $response, $arguments)
  {
    if ($arguments['uid'] === $this->container->sentinel->getUser()->username) {
      $this->flash->addMessage('error', "You can't delete yourself.");
      return $response->withRedirect($this->router->pathFor('admin.users.all'));
    }

    $user = User::where('username', $arguments['uid'])->first();
    
    $user->attr()->sync([]);
    $user->groups()->sync([]);
    
    $user->delete();

    $this->flash->addMessage('success', "User has been deleted.");
    return $response->withRedirect($this->router->pathFor('admin.users.all'));
  }
  
  public function editUser($request, $response, $arguments)
  {
    $getCurrentUserData = User::where('username', $arguments['uid'])->first();
    
    if(!$getCurrentUserData) {
      $this->flash->addMessage('error', "No user with user name '{$arguments['uid']}' found.");
      return $response->withRedirect($this->router->pathFor('admin.users.all'));     
    }
    
    $getCurrentUserRole = $this->container->sentinel->findById($getCurrentUserData->id)->roles()->get()->first();

    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $getCurrentUserData,
      'attr' => self::mapAttributes( $getCurrentUserData->attr ),
      'role' => $getCurrentUserRole->slug
    ]);

    return $this->view->render($response, 'admin/user/edit.html', self::userOptions());
  }

  public function postEditUser($request, $response, $arguments)
  {
    $getCurrentUserData = User::where('username', $arguments['uid'])->first();
    
    $getCurrentUserRole = $this->container->sentinel->findById($getCurrentUserData->id);
    
    $systemSalt = $this->container->get('settings')['default_salt'];
    $defaultPassword = substr( base64_encode($request->getParam('email') . $systemSalt), 3, 8);  
    
    if($getCurrentUserRole) {
      $getCurrentUserRole = $getCurrentUserRole->roles()->get()->first();
    }
    
    $credentials = [
      'username' => $request->getParam('username'),
      'displayname' => $request->getParam('displayname'),
      'email' => $request->getParam('email'),
      'first_name' => $request->getParam('first_name'),
      'last_name' => $request->getParam('last_name'),
      'org_notes' => $request->getParam('org_notes'),
      'hash' => substr( hash_hmac('sha1', $request->getParam('email'), $systemSalt.$defaultPassword), 12, 36),
    ];

    // change users password
    if ($request->getParam('password')) {
      $credentials['password'] = $request->getParam('password');
    }

    // change users role
    if ($getCurrentUserRole) {
      $role = $this->container->sentinel->findRoleBySlug($getCurrentUserRole->slug);
      $role->users()->detach($getCurrentUserData);
    }
    
    $role = $this->container->sentinel->findRoleBySlug($request->getParam('role'));
    $role->users()->attach($getCurrentUserData);

    // update user data
    $this->container->sentinel->update($getCurrentUserData, $credentials);

    $requestData = self::handlePostData($request);
    $getCurrentUserData->attr()->sync($requestData['attributes']);
    $getCurrentUserData->groups()->sync($requestData['groups']);
    
    $this->flash->addMessage('success', "User details for '{$credentials['username']}' have been changed.");
    return $response->withRedirect($this->router->pathFor('admin.users.all'));
  }
  
  public function addUser($request, $response, $arguments)
  {
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'attr' => [],
      'role' => 'user',
      'new' => true
    ]);

    return $this->view->render($response, 'admin/user/edit.html', self::userOptions());
  }
  
  public function postAddUser($request, $response, $arguments){
    $credentials = [
      'username' => $request->getParam('username'),
      'displayname' => $request->getParam('displayname'),
      'email' => $request->getParam('email'),
      'first_name' => $request->getParam('first_name'),
      'last_name' => $request->getParam('last_name'),
      'org_notes' => $request->getParam('org_notes'),
      'password' => $request->getParam('password'),
    ];

    $validation = $this->validator->validate($request, [
      'username' => v::noWhitespace()->notEmpty()->userAvailable(),
      'email' => v::noWhitespace()->notEmpty()->emailAvailable(),
      'password' => v::noWhitespace()->notEmpty(),
    ]);

    if ($validation->failed()) {
      $this->flash->addMessage('error', "Validation of user '{$credentials['username']}' failed.");
      return $response->withRedirect($this->router->pathFor('admin.users.all'));
    }

    $systemSalt = $this->container->get('settings')['default_salt'];
    $defaultPassword = substr( base64_encode($credentials["email"] . $systemSalt), 3, 8);
    $credentials['hash'] = substr( hash_hmac('sha1', $credentials["email"], $systemSalt.$defaultPassword), 12, 36);
    
    $user = $this->container->sentinel->registerAndActivate($credentials);
    
    $role = $this->container->sentinel->findRoleByName('User');
    $role->users()->attach($user);
    
    $requestData = self::handlePostData($request);
    $user->attr()->sync($requestData['attributes']);
    $user->groups()->sync($requestData['groups']);

    $this->flash->addMessage('success', "User '{$credentials['username']}' have been successfully registered.");
    return $response->withRedirect($this->router->pathFor('admin.users.all'));
  }
  
  public function createFromOrderAndAttest($request, $response, $arguments) {
    
    $order = Order::where('id', $arguments['uid'])->first();
    
    if(!$order) {
      $this->flash->addMessage('error', "No such order found.");
      return $response->withRedirect($this->router->pathFor('admin.order.attest', [ 'uid' => $arguments['uid'] ]));      
    }

    $systemSalt = $this->container->get('settings')['default_salt'];
    $defaultPassword = substr( base64_encode($order->email . $systemSalt), 3, 8);
    
    $credentials = [
      'username' => $order->email,
      'email' => $order->email,
      //This generates the users default password, to be changed later - unsecure
      'password' => $defaultPassword,
    ];

    $validators = [
      'username' => v::noWhitespace()->notEmpty()->userAvailable(),
      'email' => v::noWhitespace()->notEmpty()->emailAvailable(),
      'password' => v::noWhitespace()->notEmpty(),
    ];
   
    $validated = array_filter(
      array_map(function ($k, $v) use ($credentials) { 
        return $v->validate($credentials[$k]);
      }, array_keys($validators), $validators)
    );

    if (count($validated) !== count($credentials)) {
      $this->flash->addMessage('error', "Validation of user '{$credentials['username']}' failed.");
      return $response->withRedirect($this->router->pathFor('admin.order.attest', [ 'uid' => $order->id ]));
    }
    
    $systemSalt = $this->container->get('settings')['default_salt'];
    
    $user = $this->container->sentinel->registerAndActivate($credentials);
    $user->update([
      'hash' => substr( hash_hmac('sha1', $order->email, $systemSalt.$defaultPassword), 12, 36)
    ]);
      
    $role = $this->container->sentinel->findRoleByName('User');
    $role->users()->attach($user);
    
    //Attest order with new user
    $res = $order->update([
      'user_id' => $user->id, 
      'attested_id' => $this->container->sentinel->getUser()->id, 
    ]);
    
    if($res) {
      $attest_info = " and the order has been attested.";
      
      $template = Post::where('slug', 'welcome-email')->first();
      
      if( self::sendEmail(
        $user->email, // Recipiant
        $template->title,      // Subject Line
        $template->content,    // E-mail Body
        [                      // Values ([{###}] where ### is the KEY)
          "INVITE_CODE" => $user->hash,
        ]
      ) ) {
        $attest_info .= " Email invite sent to " . $user->email;
      } else {
        $attest_info .= " Email invite could not be sent to " . $user->email . " (Sorry)";
      };
            
    } else {
      $attest_info = ", but the order could not be attested.";
    }    
    
    $this->flash->addMessage('success', "User '{$credentials['username']}' have been successfully registered" . $attest_info);
    return $response->withRedirect($this->router->pathFor('admin.orders.all'));
  }
}
