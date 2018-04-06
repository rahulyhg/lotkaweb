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
      'npc',
      'checked_in',
    ];
  }
  
  private function userOptions() {
    return [
      'characters' => Character::orderBy('name')->get(),
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
    $attr = [];
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
      
      if(isset($attr[$attr_key]) && is_array($attr[$attr_key])) {
        $attr[$attr_key][] = $attributes['values'][$i];
      } elseif (isset($attr[$attr_key])) {
        $attr[$attr_key] = [$attr[$attr_key], $attributes['values'][$i]];
      } else {
        $attr[$attr_key] = $attributes['values'][$i];      
      }
    }
    
    $groups = $request->getParam('group_ids');
    $groups = is_array($groups) ? $groups : [$groups];

    return [ 
      'attributes' => $attribute_ids,
      'attr' => $attr, 
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
        "attr" => self::mapAttributes( $user->attr ),
        "character" => $user->character, 
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
    $getCurrentUserData = User::where('id', $arguments['uid'])->first();
    
    if(!$getCurrentUserData) {
      $this->flash->addMessage('error', "No user with user name '{$arguments['uid']}' found.");
      return $response->withRedirect($this->router->pathFor('admin.users.all'));     
    }
    
    $getCurrentUserRole = $this->container->sentinel->findById($getCurrentUserData->id)->roles()->get()->first();
    $current = [
      'data' => $getCurrentUserData,
      'attr' => self::mapAttributes( $getCurrentUserData->attr ),
      'role' => $getCurrentUserRole->slug
    ];
    
    $user_character = Character::where('user_id', $getCurrentUserData->id)->first();
    if($user_character) {
      $current['data']['character_id'] = $user_character->id;
    }
    
    $this->container->view->getEnvironment()->addGlobal('current', $current);

    return $this->view->render($response, 'admin/user/edit.html', self::userOptions());
  }

  private function removeUsersFromCharacter($character_id) {
    $characters = Character::where('id', $character_id)->get();      
    foreach ($characters as $character) {
      $character->user_id = 0;
      $character->save();
    }
  }  
  
  public function postEditUser($request, $response, $arguments)
  {
    $getCurrentUserData = User::where('id', $arguments['uid'])->first();
    
    $getCurrentUserRole = $this->container->sentinel->findById($getCurrentUserData->id);
    
    $systemSalt = $this->container->get('settings')['default_salt'];
    $defaultPassword = substr( base64_encode($request->getParam('email') . $systemSalt), 3, 8);  
    
    if($getCurrentUserRole) {
      $getCurrentUserRole = $getCurrentUserRole->roles()->get()->first();
    }
    
    $requestData = self::handlePostData($request);
    
    $character_id = $request->getParam('character_id');
    if(is_null($character_id)) {
      $character = Character::where('user_id', $getCurrentUserData->id)->first();      
      if($character) {
        $character->user_id = 0;
        $character->save();
      }
    } else {
      $character = Character::where('id', $character_id)->first();
      if($character) {
        self::removeUsersFromCharacter($character_id);
        $character->user_id = $getCurrentUserData->id;
        $character->save();        
        
        $order = Order::where('user_id', $getCurrentUserData->id)->first();

        $character_data = [
          'user_id' => $getCurrentUserData->id,
        ];
        
        if($order && strlen($character->name) == 0) {
          $character_data['name'] = $order->name;   
        }
        
        $groups_lookup = [$requestData["attr"]["group"], $requestData["attr"]["shift"]];
        $group_ids = [];
        foreach ($groups_lookup as $group_name) {
          if(strlen($group_name)) $group_ids[] = Group::firstOrCreate(['name' => $group_name])->id;       
        }
        
        $char_attribs = [
          "org" => $requestData["attr"]["group"], 
          "shift" => $requestData["attr"]["shift"],
          "role" => $requestData["attr"]["role"],
          "gender" => $requestData["attr"]["char_gender"]
        ];
        
        $attribute_ids = [];
        foreach ($char_attribs as $name => $value) {
          if($value) {
            $attribute_ids[] = Attribute::firstOrCreate([
              'name' => $name, 
              'value' => $value
            ])->id;
          }
        }
                
        $character->groups()->sync($group_ids);
        $character->attr()->sync($attribute_ids, false);
        
        $character->update($character_data);
      }
    }    
        
    $credentials = [
      'character_id' => $request->getParam('character_id'),
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
      'order_id' => $order->id,
    ];

    $validators = [
      'username' => v::noWhitespace()->notEmpty()->userAvailable(),
      'email' => v::noWhitespace()->notEmpty()->emailAvailable(),
      'password' => v::noWhitespace()->notEmpty(),
      'order_id' => v::noWhitespace()->notEmpty(),
    ];

    $validation_fails = "";
    $validated = array_filter(
      array_map(function ($k, $v) use ($credentials) { 
         if($v->validate($credentials[$k])) {
           return true;
         } else {
           $validation_fails .= " {$k}";
           return false;
         }           
      }, array_keys($validators), $validators)
    );

    if (in_array(false, $validated)) {
      $this->flash->addMessage('error', "Validation of user '{$credentials['username']}' failed. ({$validation_fails} not valid)");
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
  
  public function makeRole($request, $response, $arguments) {
    if(isset($arguments['name'])) {
      $rolename = strtolower($arguments['name']);
      $role = $this->container->sentinel->getRoleRepository()->createModel()->create([
          'name' => ucfirst($rolename),
          'slug' => $rolename,
      ]);
      
      die(var_dump($role));
    }
  }
  
  
  private function getParticipantFromHash($arguments) {
    $user_hash = filter_var(isset($arguments['hash']) ? $arguments['hash'] : 'nohash', FILTER_SANITIZE_STRING);
    $user = User::where('hash', $user_hash)->first();
    $participant = [];
      
    if($user) {
      $participant["user"] = $user;
      $participant["attributes"] = self::mapAttributes($user->attr);
      $participant["hash"] = $user_hash;
    } else {
      $participant = $user_hash;
    }
    
    return $participant;
  }
  
  public function checkIn($request, $response, $arguments) {
    $participant = self::getParticipantFromHash($arguments);
    $template = Post::where('slug', 'checkin')->first()->content;
    $double = false;
    if(!isset($participant["user"])) {
      $participant = false;
    } else {
      if($participant["user"]->attr->where('name', 'checked_in')->whereIn('value', ['1', 'on'])->count() > 0) {
        $double = true;
      } else {
        self::setAttribute($participant["user"], "checked_in", "1");        
      }
    }

    return $this->view->render($response, '/new/barebones.html', [
      'content' => $template,
      'participants' => User::where([['character_id', '<>', 0],['displayname', '<>', '']])->orderBy('displayname')->get(),
      'participant' => $participant,
      'double' => $double,
    ]);  
  }
  
  public function manualCheckIn($request, $response, $arguments) {
    $participant = [];
    $revert = false;
    $checkin = false;
    $hash = [];
    if($request->getParam('revert_checkin')) {
      $hash['hash'] = $request->getParam('revert_checkin');
      $revert = true;
    }
    if($request->getParam('checkin')) {
      $hash['hash'] = $request->getParam('checkin');
      $checkin = true;
    }
    
    $participant = self::getParticipantFromHash($hash);
    
    if(!isset($participant["user"])) {
      $this->flash->addMessage('waning', "No participant with this code ($participant) found!"); 
      return $response->withRedirect($this->router->pathFor('admin.checkin', [ 'hash' => 'list' ]));
    } else {
      if($revert) {
        self::removeAttribute($participant["user"], "checked_in");
        $this->flash->addMessage('warning', "Participant check-in reverted!");
      }
      if($checkin) {
        self::setAttribute($participant["user"], "checked_in", "1");        
        $this->flash->addMessage('success', "Participant checked-in!");
      }
    }
    return $response->withRedirect($this->router->pathFor('admin.checkin', [ 'hash' => 'list' ]));
  }
  
  public function namesign($request, $response, $arguments) {
    $participant = self::getParticipantFromHash($arguments);
    $template = Post::where('slug', 'namesign')->first()->content;
    
    return $this->view->render($response, '/new/barebones.html', [
      'content' => $template,
      'participant' => $participant,
    ]);     
  }

  public function exportItems($request, $response, $arguments)
  {
    $DB = $this->db->getDatabaseManager();
    $sql = "SELECT c.id, c.name, a.name as attr, a.value, a.id as attr_id FROM attributes a JOIN user_attribute ua ON a.id = ua.attribute_id JOIN users u ON ua.user_id = u.id JOIN characters c ON u.character_id = c.id WHERE a.name IN ('packing_pnqs', 'packing_ta') AND a.value NOT LIKE '' ORDER BY c.name";
    $owners = $DB->select( $DB->raw( $sql ) );
    $itemData = [
      "pnqs" => [],
      "ta" => [],
    ];

    foreach($owners as $id => $owenrData) {
      $lists = [
        "pnqs" => $owenrData->attr && $owenrData->attr == "packing_pnqs" ? preg_split('/\r\n|\r|\n/', $owenrData->value) : [],
        "ta" => $owenrData->attr && $owenrData->attr == "packing_ta" ? preg_split('/\r\n|\r|\n/', $owenrData->value) : [],
      ];
      $assignee = isset($owenrData->name) ? $owenrData->name : "[ NO ASSIGNEE ]";
      $assignee_id = isset($owenrData->id) ? $owenrData->id : "[ NO ASSIGNEE ]";
      
      foreach($lists as $list_type => $listData) { 
        if(count($listData) > 0) {
          foreach($listData as $id => $item) {
            $itemData[$list_type][] = [
              "type" => $list_type,
              "assignee" => $assignee,
              "assignee_id" => $assignee_id,
              "desc" => $item,
            ];
          }
        }
      }
    }
    
    #die(var_dump($itemData));
    
    return $this->view->render($response, 'admin/participants/characters/exportItems.html', [
      'lists' => $itemData
    ])->withHeader('Content-Type', 'text/csv');
  }  
}
