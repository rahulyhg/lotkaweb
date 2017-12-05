<?php
namespace App\Controllers\Page\Participant;

use App\Models\Post;
use App\Models\Character;
use App\Models\Plot;
use App\Models\Group;
use App\Models\Relation;
use App\Models\Attribute;
use App\Models\User;
use App\Models\Order;
use App\Models\Task;

use App\Controllers\Controller;
use App\Controllers\Admin\UserActionController;

use App\Mail\Sender;
use App\Mail\Templater;

use Slim\Views\Twig as View;

class OnboardingPageController extends Controller
{
  
  private function sendEmail($recipient, $subject, $body, $vars) {
    $mail = new Sender($this->container->get('settings'));
    return $mail->send($recipient, $subject, $body, $vars);
  }
  
  private function onboardingAttributes() {
    return [
'allergies', 
'aspects', 
'birth_date', 
'care_of', 
'char_dir_avo', 
'char_gender', 
'char_ind_col', 
'char_iso_int', 
'char_log_int', 
'char_mal_con', 
'char_mil_dem', 
'char_nos_pro', 
'char_phy_non', 
'city', 
'country', 
'emergency_contact', 
'emergency_phone', 
'feedback',
'gender', 
'group',
'id_number_swe', 
'medical_conditions',
'membership_date', 
'membership_fee', 
'onboarding_complete', 
'onboarding_stage',
'onefifty_plus', 
'password_set',
'phone', 
'player_connections', 
'player_unwanted', 
'portrait',       
'postal_code', 
'pref_bus',
'pref_conflict_ideological', 
'pref_conflict_intrapersonal', 
'pref_counselling', 
'pref_everyday', 
'pref_friendships', 
'pref_interrupted_sleep', 
'pref_late_arival', 
'pref_ooc_leader',
'pref_ooc_medic', 
'pref_ooc_radio',
'pref_player_def_1', 
'pref_player_def_2', 
'pref_player_def_3', 
'pref_responsibilities', 
'pref_romance', 
'pref_secrets', 
'pref_work', 
'size', 
'state', 
'street_address_1', 
'street_address_2',
'terms_accepted',
'torso_circumference', 
    ];
  }
  
  private function getUserData($request, $participant) {
    $user = $participant['user'];
    $user_data_set = [ 'username', 'email', 'first_name', 'last_name' ];
    $user_data = [];
    foreach ($user_data_set as $attr) {
      if ( strlen($request->getParam($attr)) ) {
        $user_data[$attr] = $request->getParam($attr);
      }
    }
    
    if($request->getParam('first_name') || $request->getParam('last_name')) {
      $user_data['displayname'] = "{$request->getParam('first_name')} {$request->getParam('last_name')}";
    }
    
    if (($request->getParam('password') && $request->getParam('password_repeat')) && 
        ($request->getParam('password') == $request->getParam('password_repeat'))) {
      $user_data['password'] = $request->getParam('password');
      $participant["attributes"]["password_set"] = true;
      self::updateAttributes(['password_set' => true], $user);
    }
    
    return $user_data;
  }
  
  
  private function getAttributeIds($attributes = [ 'keys' => [], 'values' => [] ]) {
    $attribute_ids = [];
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
    }
    return $attribute_ids;
  }
  
  private function setUserAttributes($request, $participant) {
    $user_attribute_set = self::onboardingAttributes();
    $user_attributes = $participant["attributes"];
    $request_attributes = $request->getParsedBody();

    # We prepopulate boolean attributes so they can be deactivated, otherwise they are not even sent
    if( isset($request_attributes["boolean_attributes"]) ) {
      $boolean_attributes = explode(',', $request->getParam("boolean_attributes"));
      foreach ($boolean_attributes as $a) {
        $request_attributes[$a] = isset($request_attributes[$a]) ? $request_attributes[$a] : 'false';
      }
    }
    
    $attributes = [ 'keys' => [], 'values' => [] ];

    foreach ($user_attribute_set as $attr) {
      $attribute_value = null;
      
      if(array_key_exists($attr, $user_attributes)) $attribute_value = $user_attributes[$attr]; //Prepolulate existing attr
      if(isset($request_attributes[$attr])) $attribute_value = $request_attributes[$attr]; //Update if we have new values

      if ( is_array($attribute_value) ? count($attribute_value) : strlen($attribute_value) ) {
        if(is_array($attribute_value)) {
          foreach ($attribute_value as $i => $val) {
            $attributes['keys'][] = $attr;
            $attributes['values'][] = $attribute_value[$i];
          }
        } else {
          $attributes['keys'][] = $attr;
          $attributes['values'][] = $attribute_value;
        }
      }
    }

    $updated_attribute_ids = self::getAttributeIds( $attributes );
    
    return $participant["user"]->attr()->sync($updated_attribute_ids);
  }

  private function updateAttributes($attributes, $user) {
    foreach ($attributes as $name => $value) {
      $existing_attribute = $user->attr()->where('name', $name)->get();
      if(!$existing_attribute->isEmpty()) {
        $user->attr()->detach($existing_attribute); 
      }
      
      if(is_array($value)) {
        foreach ($value as $i => $val) {
          $user->attr()->attach(Attribute::firstOrCreate(['name' => $name, 'value' => $val]));
        }
      } else {
        $user->attr()->attach(Attribute::firstOrCreate(['name' => $name, 'value' => $value]));
      }
    }
  }  
  
  private function uploadFile($uploadedFiles, $user) {
    $directory = $this->container->get('settings')['user_images'];

    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['portrait'];
    
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
      $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
      $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
      $filename = sprintf('%s.%0.8s', $basename, $extension);

      $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
      
      self::updateAttributes(['portrait' => $filename], $user);
    }
  }  
  
  private function getCurrentUser($hash) {
    $participant = User::where('hash', $hash)->first();
    
    return $participant ? [
      "user" => $participant,
      "attributes" => self::mapAttributes( $participant->attr ),
    ] : false;
  }
  
  private function getStageData($arguments, $participant) {
    $min_stage = isset($participant["attributes"]["password_set"]) ? 2 : 1;
    $stage = isset($arguments['stage']) ? $arguments['stage'] : $min_stage;
    $stage = filter_var($stage, FILTER_SANITIZE_NUMBER_INT);
    $user_stage = isset($participant["attributes"]["onboarding_stage"]) ? 
      $participant["attributes"]["onboarding_stage"] : $min_stage;
      
    $stage_nr = $user_stage == $stage ? $user_stage : min($user_stage, $stage);
    
    return [
      'stage' => $stage,
      'stage_nr' => $stage_nr,
      'total' => Post::where('slug', 'like', "stage-%")->get()->count(),
    ];
  }
  
  public function onboarding($request, $response, $arguments)
  {    
    $user_hash = filter_var($arguments['hash'], FILTER_SANITIZE_STRING);
    $participant = self::getCurrentUser($user_hash);
    
    if(!$participant["user"] || isset($participant["attributes"]["onboarding_complete"])) {
      $this->flash->addMessage(
        'error', 
        "Sorry, this participant could not be found for onboarding or the link has allready been used."
      );
      return $response->withRedirect($this->router->pathFor('home'));
    }
    
    $user_order = Order::where('user_id', $participant["user"]->id)->first();

    if(!$user_order) {
      $this->flash->addMessage(
        'error', 
        "Sorry, there's no ticket connected to this user. Particians need a ticket to be onboarded."
      );
      return $response->withRedirect($this->router->pathFor('home'));
    }
    
    $stage_data = self::getStageData($arguments, $participant);

    $stage = Post::where('slug', "stage-{$stage_data['stage_nr']}")->first();

    $torso_size = [];
    $torso_size[] = [
      'code' => 70, 'description' => 'Up to 70cm (28")'
    ];
    for ($i = 70; $i <= 200; $i+=5) {
      $torso_size[] = [ 
        'code' => $i+5, 
        'description' => "$i to " . ($i+5) . "cm (" . ceil($i/2.54) . "-" . ceil(($i+5)/2.54) . "\")" 
      ];
    }
    $torso_size[] = [
      'code' => 210, 'description' => 'Over 205cm (81")'
    ];
    
    $this->container->view->getEnvironment()->addGlobal('data', [
      'genders' => ['Non-binary','Female','Male','Other'],
      'sizes' => [
        [ 'code' => 'SMALL',  'description' => "up to 165cm (<5'5\") and 60kg (<130lbs)"   ],
        [ 'code' => 'MEDIUM', 'description' => "165-175cm (5'9\"), up to 85kg (187lbs)"  ],
        [ 'code' => 'LARGE',  'description' => "175-190cm (6'3\"), up to 100kg (220lbs)" ],
        [ 'code' => 'XLARGE', 'description' => "over 190cm (>6'3\"), over 100kg (>220lbs)" ], 
      ],
      'torso_circumference' => $torso_size,
      'user_order' => $user_order,
    ]);
            
    return $this->view->render($response, '/new/participant/onboarding/stages.html', [
      'stage_nr' => $stage_data['stage_nr'],
      'stages_total' => $stage_data['total'],
      'stage' => $stage,
      'participant' => $participant,
      'hash' => $user_hash,
    ]);
  }
  
  public function save($request, $response, $arguments)
  {
    $user_hash = filter_var($arguments['hash'], FILTER_SANITIZE_STRING);
    $participant = self::getCurrentUser($user_hash);
    
    if(!$participant) {
      return $response->withRedirect($this->router->pathFor('home'));
    }
    
    $stage_data = self::getStageData($arguments, $participant);
    
    $participant['attributes']['onboarding_stage'] = $stage_data['stage'] + 1;
    $participant['attributes']['onboarding_complete'] = $stage_data['total'] == $stage_data['stage'];
    
    $user_data = self::getUserData($request, $participant);
    
    self::setUserAttributes($request, $participant);
    
    # Check if we have updated data
    $hasUpload = $request->getUploadedFiles();
    if( isset($hasUpload['portrait']) ) {
      self::uploadFile($hasUpload, $participant['user']);
    }
    
    if( !$this->container->sentinel->update($participant['user'], $user_data) ) {
      $this->flash->addMessage('error', "Something seems to be out of wack, " . 
                               "have a look and make sure that you have filled out all the required fields.");
    }
    
    if($participant['attributes']['onboarding_complete']) {
      
      $user_role = $this->container->sentinel->findRoleBySlug('user');
      $user_role->users()->detach($participant["user"]);
      
      $participant_role = $this->container->sentinel->findRoleBySlug('participant');
      $participant_role->users()->attach($participant["user"]);      
      
      $template = Post::where('slug', 'post-register-email')->first();
      
      if( self::sendEmail(
        $participant["user"]->email, // Recipiant
        $template->title,      // Subject Line
        $template->content,    // E-mail Body
        [                      // Values ([{###}] where ### is the KEY)
          
        ]
      ) ) {
        $this->flash->addMessage('success', "You have successfully been registered!");
        return $response->withRedirect($this->router->pathFor('open.page',['category' => 'onboarding-complete']));
      } else {
        $this->flash->addMessage('error', "Something went wrong, you could not be registered. Please contact the Organizers.");
      };      
    }
    
    return $response->withRedirect($this->router->pathFor(
      'participant.onboarding', 
      ['hash' => $user_hash, 'stage' => $participant['attributes']['onboarding_stage']]
    ));
  }  
}
